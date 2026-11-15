<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\GeneratorOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class NodeExamGeneratorService
{
    /**
     * Locate the system node binary
     */
    public static function getNodeBinary(): ?string
    {
        try {
            $output = @shell_exec('node -v 2>&1');
            if ($output && str_starts_with(trim($output), 'v')) {
                return 'node';
            }

            $candidates = [
                '/usr/lib/ispnodejs/bin/node',
                '/usr/bin/node',
                '/usr/local/bin/node',
                '/opt/homebrew/bin/node',
                '/root/.nvm/versions/node/v22.0.0/bin/node',
            ];

            foreach ($candidates as $candidate) {
                $ver = @shell_exec("$candidate -v 2>&1");
                if ($ver && str_starts_with(trim($ver), 'v')) {
                    return $candidate;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Check if Node.js exam generator is available (either via HTTP daemon or direct CLI)
     */
    public static function isAvailable(): bool
    {
        // 1. Check if HTTP microservice daemon is responding
        $url = rtrim((string) config('services.exam_generator.url', 'http://127.0.0.1:3005'), '/');

        try {
            $response = Http::baseUrl($url)
                ->timeout(1)
                ->get('/health');

            if ($response->successful() && ($response->json('status') === 'ok' || $response->json('browserConnected') === true)) {
                return true;
            }
        } catch (Throwable) {
            // HTTP daemon not up, check CLI fallback below
        }

        // 2. Check if local CLI runner and node binary are present
        $cliScript = base_path('edusfera-exam-generator/src/cli.js');
        if (! file_exists($cliScript)) {
            return false;
        }

        return self::getNodeBinary() !== null;
    }

    /**
     * Generate PDF bundle via edusfera-exam-generator (HTTP daemon with automatic CLI fallback)
     */
    public static function generate(GeneratorOrder $order, array $parsedData, string $targetPdfPath): bool
    {
        $url = rtrim((string) config('services.exam_generator.url', 'http://127.0.0.1:3005'), '/');
        $timeout = (int) config('services.exam_generator.timeout', 30);

        try {
            $variantsPayload = [];
            $allVariants = $parsedData['variants'] ?? [];

            foreach ($allVariants as $idx => $variant) {
                $varNum = $variant['number'] ?? ($idx + 1);
                $partA = [];
                $partB = [];

                foreach ($variant['part_a'] ?? [] as $t) {
                    $options = [];
                    foreach ($t['options'] ?? [] as $opt) {
                        $options[] = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
                    }

                    $partA[] = [
                        'number' => $t['num'] ?? (count($partA) + 1),
                        'points' => 1,
                        'text' => $t['condition'] ?? $t['condition_html'] ?? $t['prompt'] ?? '',
                        'choices' => $options,
                        'answer' => $t['correct'] ?? '',
                        'explanation' => $t['explanation'] ?? '',
                    ];
                }

                foreach ($variant['part_b'] ?? [] as $t) {
                    $partB[] = [
                        'number' => $t['num'] ?? (count($partB) + 1),
                        'points' => 2,
                        'text' => $t['condition'] ?? $t['condition_html'] ?? $t['prompt'] ?? '',
                        'answer' => $t['correct'] ?? '',
                        'explanation' => $t['explanation'] ?? '',
                    ];
                }

                $variantsPayload[] = [
                    'number' => $varNum,
                    'title' => "Вариант {$varNum}",
                    'partA' => $partA,
                    'partB' => $partB,
                ];
            }

            if (empty($variantsPayload)) {
                return false;
            }

            $payload = [
                'meta' => [
                    'title' => $parsedData['testTitle'] ?: ('Проверочная работа: ' . $order->topic),
                    'subject' => $order->subject ?: 'Математика',
                    'grade' => $parsedData['gradeLabel'] ?? ($order->payload['grade'] ?? '7 класс'),
                    'school' => 'Edusfera.by',
                    'durationMinutes' => (int) ($parsedData['timeMinutes'] ?? 45),
                    'variant' => 1,
                ],
                'options' => [
                    'showAnswers' => true,
                    'showExplanations' => true,
                    'fontSizePt' => 9.5,
                ],
                'variants' => $variantsPayload,
            ];

            // 1. Primary path: try fast HTTP daemon
            try {
                $response = Http::baseUrl($url)
                    ->accept('application/pdf')
                    ->timeout(10)
                    ->post('/render-pdf', $payload);

                if ($response->successful()) {
                    $pdfBinary = $response->body();
                    if (str_starts_with($pdfBinary, '%PDF-')) {
                        Storage::disk('local')->put($targetPdfPath, $pdfBinary);
                        return true;
                    }
                }
            } catch (Throwable $httpEx) {
                Log::info('Node HTTP daemon not active, executing CLI fallback: ' . $httpEx->getMessage());
            }

            // 2. Secondary path: direct Node CLI execution (guarantees KaTeX rendering even without daemon)
            return self::generateViaCli($payload, $targetPdfPath, $timeout);
        } catch (Throwable $e) {
            Log::warning('Node exam generator invocation error: ' . $e->getMessage(), [
                'order' => $order->order_number,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Fallback: direct headless CLI invocation
     */
    protected static function generateViaCli(array $payload, string $targetPdfPath, int $timeout = 30): bool
    {
        $nodeBinary = self::getNodeBinary();
        $cliScript = base_path('edusfera-exam-generator/src/cli.js');

        if (! $nodeBinary || ! file_exists($cliScript)) {
            return false;
        }

        $destination = Storage::disk('local')->path($targetPdfPath);
        $destDir = dirname($destination);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $tempJson = storage_path('app/temp_node_input_' . uniqid() . '.json');

        try {
            file_put_contents($tempJson, json_encode($payload, JSON_UNESCAPED_UNICODE));

            $env = [
                'PATH' => '/usr/lib/ispnodejs/bin:/usr/local/bin:/usr/bin:/bin:' . (getenv('PATH') ?: ''),
            ];

            $process = new Process([
                $nodeBinary,
                $cliScript,
                '--json',
                $tempJson,
                '--out',
                $destination,
            ], env: $env);

            $process->setWorkingDirectory(base_path('edusfera-exam-generator'));
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($destination) || filesize($destination) < 1000) {
                Log::warning('Node CLI exam generator failed: ' . $process->getErrorOutput());
                return false;
            }

            return true;
        } catch (Throwable $cliError) {
            Log::warning('Node CLI exam generator error: ' . $cliError->getMessage());
            return false;
        } finally {
            if (file_exists($tempJson)) {
                @unlink($tempJson);
            }
        }
    }
}
