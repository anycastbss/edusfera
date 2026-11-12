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

    /** Convert parser HTML to plain text while preserving LaTeX delimiters and line breaks. */
    private static function conditionToPlainText(array $task): string
    {
        $raw = (string) ($task['condition_html'] ?? $task['prompt'] ?? '');
        $raw = preg_replace('/<br\s*\/?\s*>/iu', "\n", $raw) ?? $raw;
        $plain = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{3,}/u", "\n\n", $plain) ?? $plain);
    }

    /** Save parser-provided inline SVG/PNG diagrams into the isolated job directory. */
    private static function materializeDiagram(array $task, string $tmpDir, string $name): ?string
    {
        $encoded = (string) ($task['diagram_b64'] ?? '');
        if ($encoded === '') {
            return null;
        }
        $bytes = base64_decode($encoded, true);
        if ($bytes === false || strlen($bytes) < 16 || strlen($bytes) > 2_000_000) {
            throw new \UnexpectedValueException("Некорректная или слишком большая схема в {$name}.");
        }

        $assetsDir = $tmpDir . DIRECTORY_SEPARATOR . 'assets';
        if (! is_dir($assetsDir) && ! mkdir($assetsDir, 0700, true) && ! is_dir($assetsDir)) {
            throw new \RuntimeException('Не удалось создать каталог графических ресурсов PDF.');
        }

        if (str_starts_with(ltrim($bytes), '<svg') || str_contains(substr($bytes, 0, 300), '<svg')) {
            $svg = (string) $bytes;
            if (preg_match('/<script|<foreignObject|(?:href|src)\\s*=\\s*["\\\']https?:/iu', $svg)) {
                throw new \UnexpectedValueException("Схема {$name} содержит запрещённую SVG-разметку.");
            }
            $path = $assetsDir . DIRECTORY_SEPARATOR . $name . '.svg';
        } elseif (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            $path = $assetsDir . DIRECTORY_SEPARATOR . $name . '.png';
        } elseif (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            $path = $assetsDir . DIRECTORY_SEPARATOR . $name . '.jpg';
        } else {
            throw new \UnexpectedValueException("Формат схемы {$name} не поддерживается.");
        }

        file_put_contents($path, $bytes, LOCK_EX);
        return $path;
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
            $isSchoolFormat = ($parsedData['examFormat'] ?? '') === 'school';
            if (! empty($parsedData['validationErrors'])) {
                throw new \UnexpectedValueException('Контент не прошёл проверку: ' . implode(' ', $parsedData['validationErrors']));
            }
            if (empty($allVariants)) {
                throw new \UnexpectedValueException('Нет вариантов для экспорта.');
            }

            foreach ($allVariants as $variantIndex => $variant) {
                $varNum = $variant['number'] ?? (count($variantsPayload) + 1);
                $partA = [];
                $partB = [];

                foreach ($variant['part_a'] ?? [] as $t) {
                    $options = [];
                    foreach ($t['options'] ?? [] as $opt) {
                        $options[] = ['text' => (string)($opt['text'] ?? '')];
                    }

                    $ans = strtoupper(trim((string) ($t['correct'] ?? '')));
                    if (preg_match('/^[1-5]$/', $ans)) {
                        $ans = chr(64 + (int) $ans);
                    }
                    if (! in_array($ans, ['A', 'B', 'C', 'D', 'E'], true) || ord($ans) - 65 >= count($options)) {
                        throw new \UnexpectedValueException("Вариант {$varNum}, задание {$t['num']}: нет подтверждённого правильного ответа.");
                    }
                    if (count($options) < 4 || count($options) > 5) {
                        throw new \UnexpectedValueException("Вариант {$varNum}, задание {$t['num']}: требуется 4 или 5 вариантов ответа.");
                    }

                    $taskNumLabel = ($isSchoolFormat || ($t['is_school'] ?? false)) ? (string) $t['num'] : ('А' . $t['num']);

                    $explA = trim((string)($t['explanation'] ?? ''));
                    if (empty($explA) || str_contains($explA, 'Сверено по') || str_contains($explA, 'Ответ вносится')) {
                        $matchedOptText = '';
                        foreach ($options as $oIdx => $opt) {
                            $optLetter = chr(65 + $oIdx);
                            if ($optLetter === $ans) {
                                $matchedOptText = strip_tags(html_entity_decode((string)($opt['text'] ?? '')));
                                break;
                            }
                        }
                        $explA = "Верный ответ: {$ans}" . ($matchedOptText ? " ({$matchedOptText})" : "") . ". Обоснование подтверждено программным материалом курса.";
                    }

                    $partATask = [
                        'number' => $taskNumLabel,
                        'prompt' => self::conditionToPlainText($t),
                        'options' => $options,
                        'correct' => $ans,
                        'explanation' => $explA,
                    ];
                    $diagramPath = self::materializeDiagram($t, $tmpDir, "diagram_v{$varNum}_a{$t['num']}");
                    if ($diagramPath !== null) {
                        $partATask['diagram_path'] = $diagramPath;
                    }
                    if ($partATask['prompt'] === '') {
                        throw new \UnexpectedValueException("Вариант {$varNum}, задание {$t['num']}: пустое условие.");
                    }
                    $partA[] = $partATask;
                }

                foreach ($variant['part_b'] ?? [] as $t) {
                    $ansB = trim((string) ($t['correct'] ?? ''));
                    if ($ansB === '' || in_array(mb_strtolower($ansB), ['целое число', 'ответ', '—'], true) || str_contains($ansB, '____')) {
                        throw new \UnexpectedValueException("Вариант {$varNum}, задание {$t['num']}: нет подтверждённого краткого ответа.");
                    }

                    $taskNumLabelB = ($isSchoolFormat || ($t['is_school'] ?? false)) ? (string) $t['num'] : ('Б' . $t['num']);

                    $explB = trim((string)($t['explanation'] ?? ''));
                    if (empty($explB) || str_contains($explB, 'Сверено по') || str_contains($explB, 'Ответ вносится')) {
                        $explB = "Верный ответ: {$ansB}. Записывается точно по установленному образцу без пробелов.";
                    }

                    $partBTask = [
                        'number' => $taskNumLabelB,
                        'prompt' => self::conditionToPlainText($t),
                        'correct' => $ansB,
                        'explanation' => $explB,
                    ];
                    $diagramPath = self::materializeDiagram($t, $tmpDir, "diagram_v{$varNum}_b{$t['num']}");
                    if ($diagramPath !== null) {
                        $partBTask['diagram_path'] = $diagramPath;
                    }
                    if ($partBTask['prompt'] === '') {
                        throw new \UnexpectedValueException("Вариант {$varNum}, задание {$t['num']}: пустое условие.");
                    }
                    $partB[] = $partBTask;
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
                'grade' => $parsedData['grade'] ?? '7',
                'grade_label' => $parsedData['gradeLabel'] ?? '7 класс',
                'work_type' => $parsedData['workType'] ?? 'independent',
                'work_type_title' => $parsedData['workTypeTitle'] ?? 'Самостоятельная работа',
                'difficulty' => $parsedData['difficulty'] ?? 'base',
                'duration' => ($parsedData['timeMinutes'] ?? 90) . ' минут',
                'max_score' => (string)($parsedData['maxScore'] ?? 100),
                'website_url' => 'https://edusfera.by',
                'instructions' => $parsedData['instructionText'] ?: 'В заданиях Части А выберите один верный вариант ответа. В заданиях Части Б запишите полученное целое число. Ответы переносите в бланк внизу листа аккуратно и разборчиво.',
                'exam_format' => $parsedData['examFormat'] ?? ($isSchoolFormat ? 'school' : 'exam'),
                'print_layout' => $parsedData['printLayout'] ?? ($isSchoolFormat ? 'economy' : 'standard'),
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
            // Remove the isolated job directory, including materialized diagrams.
            if (is_dir($tmpDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $entry) {
                    $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname());
                }
                @rmdir($tmpDir);
            }
        }
    }
}

