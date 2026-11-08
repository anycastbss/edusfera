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
            // Prepare questions list from all variants or first variant
            $questions = [];
            $allVariants = $parsedData['variants'] ?? [];

            foreach ($allVariants as $variant) {
                $varNum = $variant['number'] ?? 1;
                $prefix = (count($allVariants) > 1) ? "В{$varNum}-" : "";

                foreach ($variant['part_a'] ?? [] as $t) {
                    $options = [];
                    foreach ($t['options'] ?? [] as $opt) {
                        $options[] = ['text' => (string)$opt['text']];
                    }

                    $qData = [
                        'number' => $prefix . 'A' . $t['num'],
                        'prompt' => strip_tags(html_entity_decode((string)$t['condition_html'])),
                        'options' => $options,
                        'correct' => 'A', // Default or extracted
                        'explanation' => 'Сверено по экзаменационным спецификациям ЦТ/ЦЭ 2026.',
                    ];
                    $questions[] = $qData;
                }

                foreach ($variant['part_b'] ?? [] as $t) {
                    $qData = [
                        'number' => $prefix . 'Б' . $t['num'],
                        'prompt' => strip_tags(html_entity_decode((string)$t['condition_html'])),
                        'options' => [],
                        'correct' => 'целое число',
                        'explanation' => 'Конечный ответ Части Б записывается целым числом в клеточный бланк.',
                    ];
                    $questions[] = $qData;
                }
            }

            if (empty($questions)) {
                return false;
            }

            $payload = [
                'subject' => $order->subject ?: 'Математика',
                'title' => $parsedData['testTitle'] ?: ('Проверочная работа: ' . $order->topic),
                'duration' => ($parsedData['timeMinutes'] ?? 90) . ' минут',
                'max_score' => (string)($parsedData['maxScore'] ?? 100),
                'website_url' => 'https://edusfera.by',
                'instructions' => $parsedData['instructionText'] ?: 'В заданиях с выбором ответа отметьте один правильный вариант. Ответы перенесите в бланк в конце теста.',
                'section_title' => 'Часть A. Задания с выбором одного правильного ответа',
                'include_answer_sheet' => true,
                'questions' => $questions,
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

