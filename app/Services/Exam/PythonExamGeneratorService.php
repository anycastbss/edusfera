<?php

namespace App\Services\Exam;

use App\Models\GeneratorOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PythonExamGeneratorService
{
    /**
     * Locate the python executable inside edusfera_pdf_generator/.venv or system python3
     */
    public static function getPythonBinary(): ?string
    {
        try {
            $localVenv = base_path('edusfera_pdf_generator/.venv/bin/python');
            if (@file_exists($localVenv) && @is_executable($localVenv)) {
                return $localVenv;
            }

            // Test executing python3 directly without checking paths outside open_basedir
            $output = @shell_exec('python3 --version 2>&1');
            if ($output && stripos($output, 'python') !== false) {
                return 'python3';
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Check if Python, generator.py and required libraries are available
     */
    public static function isAvailable(): bool
    {
        try {
            $generatorPy = base_path('edusfera_pdf_generator/generator.py');
            if (! @file_exists($generatorPy)) {
                return false;
            }
            $py = self::getPythonBinary();
            if (! $py) {
                return false;
            }
            // Check if reportlab is importable
            $test = @shell_exec($py . ' -c "import reportlab" 2>&1');
            return empty(trim($test ?? ''));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Generate PDF bundle via edusfera_pdf_generator
     */
    public static function generate(GeneratorOrder $order, array $parsedData, string $targetPdfPath): bool
    {
        $python = self::getPythonBinary();
        $script = base_path('edusfera_pdf_generator/generator.py');

        if (! $python || ! file_exists($script)) {
            return false;
        }

        $tmpDir = storage_path('app/temp_exam_' . $order->order_number . '_' . uniqid());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        try {
            // Prepare structured variants payload
            $variantsPayload = [];
            $allVariants = $parsedData['variants'] ?? [];

            foreach ($allVariants as $variant) {
                $varNum = $variant['number'] ?? (count($variantsPayload) + 1);
                $partA = [];
                $partB = [];

                foreach ($variant['part_a'] ?? [] as $t) {
                    $options = [];
                    foreach ($t['options'] ?? [] as $opt) {
                        $options[] = ['text' => (string)($opt['text'] ?? '')];
                    }

                    $partA[] = [
                        'number' => 'А' . $t['num'],
                        'prompt' => strip_tags(html_entity_decode((string)$t['condition_html'])),
                        'options' => $options,
                        'correct' => $t['correct'] ?? 'A',
                        'explanation' => $t['explanation'] ?? 'Сверено по спецификациям ЦТ/ЦЭ 2026.',
                    ];
                }

                foreach ($variant['part_b'] ?? [] as $t) {
                    $partB[] = [
                        'number' => 'Б' . $t['num'],
                        'prompt' => strip_tags(html_entity_decode((string)$t['condition_html'])),
                        'correct' => $t['correct'] ?? 'целое число',
                        'explanation' => $t['explanation'] ?? 'Ответ записывается целым числом в клеточный бланк.',
                    ];
                }

                if (! empty($partA) || ! empty($partB)) {
                    $variantsPayload[] = [
                        'number' => $varNum,
                        'title' => "Вариант {$varNum}",
                        'part_a' => $partA,
                        'part_b' => $partB,
                    ];
                }
            }

            if (empty($variantsPayload)) {
                return false;
            }

            $payload = [
                'subject' => $order->subject ?: 'Математика',
                'title' => $parsedData['testTitle'] ?: ('Проверочная работа: ' . $order->topic),
                'topic' => $order->topic ?: 'Стандарты ЦТ и ЦЭ 2026',
                'duration' => ($parsedData['timeMinutes'] ?? 90) . ' минут',
                'max_score' => (string)($parsedData['maxScore'] ?? 100),
                'website_url' => 'https://edusfera.by',
                'instructions' => $parsedData['instructionText'] ?: 'В заданиях Части А выберите один верный вариант ответа. В заданиях Части Б запишите полученное целое число. Ответы переносите в бланк внизу листа аккуратно и разборчиво.',
                'variants' => $variantsPayload,
                'master_table' => $parsedData['masterTable'] ?? [],
                'grading_scale' => $parsedData['gradingScale'] ?? [],
            ];

            $jsonFile = $tmpDir . '/input.json';
            file_put_contents($jsonFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $mergedOut = $tmpDir . '/complete.pdf';

            $process = new Process([
                $python,
                $script,
                '--json', $jsonFile,
                '--out-dir', $tmpDir,
                '--merge',
                '--out-merged', $mergedOut,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($mergedOut)) {
                Log::warning('Python PDF generator failed: ' . $process->getErrorOutput());
                return false;
            }

            // Ensure destination directory exists and copy result
            $destination = Storage::disk('local')->path($targetPdfPath);
            $destDir = dirname($destination);
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            copy($mergedOut, $destination);
            return true;
        } catch (\Throwable $e) {
            Log::error('Error in PythonExamGeneratorService: ' . $e->getMessage());
            return false;
        } finally {
            // Clean up temporary files
            if (is_dir($tmpDir)) {
                array_map('unlink', glob("$tmpDir/*.*"));
                @rmdir($tmpDir);
            }
        }
    }
}

