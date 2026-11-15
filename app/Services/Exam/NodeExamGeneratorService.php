<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\GeneratorOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NodeExamGeneratorService
{
    /**
     * Check if the Node.js exam generator microservice is running and healthy
     */
    public static function isAvailable(): bool
    {
        $url = rtrim((string) config('services.exam_generator.url', 'http://127.0.0.1:3005'), '/');

        try {
            $response = Http::baseUrl($url)
                ->timeout(2)
                ->get('/health');

            return $response->successful() && ($response->json('status') === 'ok' || $response->json('browserConnected') === true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Generate PDF bundle via edusfera-exam-generator microservice
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
                    'grade' => $parsedData['gradeLabel'] ?? ($order->payload['grade'] ?? '11 класс'),
                    'school' => 'Edusfera.by',
                    'durationMinutes' => (int) ($parsedData['timeMinutes'] ?? 90),
                    'variant' => 1,
                ],
                'options' => [
                    'showAnswers' => true,
                    'showExplanations' => true,
                    'fontSizePt' => 9.5,
                ],
                'variants' => $variantsPayload,
            ];

            $response = Http::baseUrl($url)
                ->accept('application/pdf')
                ->timeout($timeout)
                ->retry(2, 400, function (Throwable $e) {
                    return $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response?->status() === 503);
                })
                ->post('/render-pdf', $payload);

            if (! $response->successful()) {
                Log::warning('Node exam generator returned error status: ' . $response->status(), [
                    'order' => $order->order_number,
                    'response' => $response->body(),
                ]);
                return false;
            }

            $pdfBinary = $response->body();

            if (! str_starts_with($pdfBinary, '%PDF-')) {
                Log::warning('Node exam generator returned invalid PDF header', [
                    'order' => $order->order_number,
                ]);
                return false;
            }

            // Save binary stream directly to storage (0 disk temporary files)
            Storage::disk('local')->put($targetPdfPath, $pdfBinary);

            return true;
        } catch (Throwable $e) {
            Log::warning('Node exam generator invocation error: ' . $e->getMessage(), [
                'order' => $order->order_number,
                'exception' => $e,
            ]);
            return false;
        }
    }
}

