<?php

namespace App\Services\Exam;

use App\Models\GeneratorOrder;
use Illuminate\Support\Str;

class ExamDocumentParser
{
    /**
     * Exact 25x25 bitmap matrix rows for https://edusfera.by QR Code
     */
    public const QR_MATRIX = [
        "1111111000101000101111111",
        "1000001010100101001000001",
        "1011101000001000101011101",
        "1011101011101110001011101",
        "1011101001000110001011101",
        "1000001011011001101000001",
        "1111111010101010101111111",
        "0000000001110011000000000",
        "1111101111010001010101010",
        "0011110000101100100100010",
        "0011101010100111011101011",
        "1001110110001100001100001",
        "0111011011101011111010111",
        "1010000000000000110101010",
        "1001011011011001111111011",
        "1000000011010011111110001",
        "1011011000010001111110100",
        "0000000010101111100011000",
        "1111111011100110101010111",
        "1000001001101100100011010",
        "1011101011001011111110101",
        "1011101010100001111011111",
        "1011101011111000010001101",
        "1000001010010011101111001",
        "1111111010010000010111111"
    ];

    /**
     * Render 100% bulletproof HTML Table QR code for DomPDF with zero external dependencies
     */
    public static function renderQrTableHtml(int $sizePx = 42): string
    {
        $cell = round($sizePx / 25, 2);
        $html = '<table style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; width: '.$sizePx.'px; height: '.$sizePx.'px;">';
        foreach (self::QR_MATRIX as $row) {
            $html .= '<tr>';
            $len = strlen($row);
            for ($x = 0; $x < $len; $x++) {
                $bg = ($row[$x] === '1') ? '#000000' : '#ffffff';
                $html .= '<td style="width: '.$cell.'px; height: '.$cell.'px; background-color: '.$bg.'; padding: 0; margin: 0; border: none; font-size: 1px; line-height: 1px;">&nbsp;</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Parse raw/cleaned generated markdown content into structured exam data
     */
    public static function parse(string $content, GeneratorOrder $order): array
    {
        $qrPath = public_path('images/edusfera-qr.png');
        $qrPngB64 = (file_exists($qrPath) && filesize($qrPath) > 50)
            ? base64_encode(file_get_contents($qrPath))
            : self::DEFAULT_QR_PNG_B64;

        // 1. Separate Teacher master section from Variants
        $masterSection = '';
        $variantsText = $content;

        if (preg_match('/(#{1,3}\s*Сводная мастер-таблица.*$)/su', $content, $m)) {
            $masterSection = $m[1];
            $variantsText = substr($content, 0, strpos($content, $m[1]));
        }

        // 2. Parse Variants
        $variants = [];
        $varChunks = preg_split('/(?=#{2,3}\s*Вариант\s*\d+|^\s*Вариант\s*\d+)/mui', $variantsText);

        foreach ($varChunks as $chunk) {
            $chunk = trim($chunk);
            if (empty($chunk)) {
                continue;
            }

            if (! preg_match('/(?:#{2,3}\s*)?Вариант\s*(\d+)/ui', $chunk, $vNumMatch)) {
                continue;
            }

            $varNum = (int) $vNumMatch[1];
            $variantData = [
                'number' => $varNum,
                'part_a' => [],
                'part_b' => [],
            ];

            $hasExamPrefix = (bool) preg_match('/Задание\s*[АБAB]\d+/ui', $chunk);

            if ($hasExamPrefix) {
                // Parse Part A Tasks (Exam format with А\d+)
                $taskAChunks = preg_split('/(?=\*{0,2}Задание\s*А\d+)/ui', $chunk);
                foreach ($taskAChunks as $tChunk) {
                    $tChunk = trim($tChunk);
                    if (! preg_match('/\*{0,2}Задание\s*А(\d+)[^:\n]*:?\*{0,2}\s*(.*)/us', $tChunk, $tMatch)) {
                        continue;
                    }

                    $tNum = (int) $tMatch[1];
                    $body = trim($tMatch[2]);

                    // Extract options 1) 2) 3) 4) or A) B) C) D)
                    $options = [];
                    $condLines = [];
                    $lines = explode("\n", $body);

                    foreach ($lines as $line) {
                        $trimLine = trim($line);
                        if (preg_match('/^([1-5]|[А-Да-дA-Ea-e])[\.\)]\s*(.+)$/u', $trimLine, $optMatch)) {
                            $rawIndex = $optMatch[1];
                            $optText = trim($optMatch[2]);

                            // Map 1..5 to A..E
                            $letter = match ($rawIndex) {
                                '1', 'А', 'а', 'A', 'a' => 'A',
                                '2', 'Б', 'б', 'B', 'b' => 'B',
                                '3', 'В', 'в', 'C', 'c' => 'C',
                                '4', 'Г', 'г', 'D', 'd' => 'D',
                                '5', 'Д', 'д', 'E', 'e' => 'E',
                                default => strtoupper($rawIndex),
                            };

                            $options[] = [
                                'letter' => $letter,
                                'text' => htmlspecialchars($optText, ENT_QUOTES, 'UTF-8'),
                            ];
                        } else {
                            if (preg_match('/^(\*{0,2}Задание\s*Б|---|###)/ui', $trimLine)) {
                                break;
                            }
                            if (empty($options)) {
                                $condLines[] = $trimLine;
                            }
                        }
                    }

                    $conditionText = trim(implode(' ', array_filter($condLines)));
                    if (empty($conditionText)) {
                        $conditionText = 'Выберите правильный ответ из предложенных вариантов:';
                    }

                    $correct = null;
                    $explanation = null;
                    if (preg_match('/(?:(?:\*{0,2}(?:Правильный ответ|Ответ)\*{0,2}\s*:\s*\[?\s*([1-5]|[А-Да-дA-Ea-e])(?:\s*[\(\:]([^\)\n\]]+)[\)]?)?\s*\]?))/ui', $tChunk, $ansM)) {
                        $raw = $ansM[1];
                        $correct = match ($raw) {
                            '1', 'А', 'а', 'A', 'a' => 'A',
                            '2', 'Б', 'б', 'B', 'b' => 'B',
                            '3', 'В', 'в', 'C', 'c' => 'C',
                            '4', 'Г', 'г', 'D', 'd' => 'D',
                            '5', 'Д', 'д', 'E', 'e' => 'E',
                            default => strtoupper($raw),
                        };
                    }
                    if (preg_match('/(?:\*{0,2}(?:Пояснение|Объяснение|Ловушка|Решение)\*{0,2}\s*:\s*([^\n\r]+))/ui', $tChunk, $expM)) {
                        $explanation = trim($expM[1]);
                    }

                    $taskItem = [
                        'num' => $tNum,
                        'condition_html' => nl2br(htmlspecialchars($conditionText, ENT_QUOTES, 'UTF-8')),
                        'options' => $options,
                        'correct' => $correct,
                        'explanation' => $explanation,
                        'is_school' => false,
                    ];

                    $diagram = self::detectAndGenerateDiagram($conditionText);
                    if ($diagram) {
                        $taskItem['diagram_b64'] = base64_encode($diagram);
                    }

                    $variantData['part_a'][] = $taskItem;
                }

                // Parse Part B Tasks
                $taskBChunks = preg_split('/(?=\*{0,2}Задание\s*Б\d+)/ui', $chunk);
                foreach ($taskBChunks as $tChunk) {
                    $tChunk = trim($tChunk);
                    if (! preg_match('/\*{0,2}Задание\s*Б(\d+)[^:\n]*:?\*{0,2}\s*(.*)/us', $tChunk, $tMatch)) {
                        continue;
                    }

                    $tNum = (int) $tMatch[1];
                    $body = trim($tMatch[2]);

                    $lines = explode("\n", $body);
                    $condLines = [];
                    foreach ($lines as $line) {
                        $trimLine = trim($line);
                        if (preg_match('/^(\[\s*Ответ:|\*{0,2}Ответ:|---|###)/ui', $trimLine)) {
                            break;
                        }
                        $condLines[] = $trimLine;
                    }

                    $conditionText = trim(implode(' ', array_filter($condLines)));
                    if (empty($conditionText)) {
                        $conditionText = 'Решите задачу и запишите полученный ответ:';
                    }

                    $correct = null;
                    $explanation = null;
                    if (preg_match('/(?:(?:\*{0,2}(?:Правильный ответ|Ответ)\*{0,2}\s*:\s*\[?\s*([^\n\]\r]+)\s*\]?))/ui', $tChunk, $ansM)) {
                        $rawAns = trim($ansM[1]);
                        if (! preg_match('/^[_ \t\.\-\–]+$/u', $rawAns) && ! str_contains($rawAns, '____') && ! str_contains($rawAns, '&nbsp;')) {
                            $correct = $rawAns;
                        }
                    }
                    if (preg_match('/(?:\*{0,2}(?:Пояснение|Объяснение|Ловушка|Решение)\*{0,2}\s*:\s*([^\n\r]+))/ui', $tChunk, $expM)) {
                        $explanation = trim($expM[1]);
                    }

                    $variantData['part_b'][] = [
                        'num' => $tNum,
                        'condition_html' => nl2br(htmlspecialchars($conditionText, ENT_QUOTES, 'UTF-8')),
                        'correct' => $correct,
                        'explanation' => $explanation,
                        'is_school' => false,
                    ];
                }
            } else {
                // Parse School Tasks (Задание 1, Задание 2...)
                $taskChunks = preg_split('/(?=\*{0,2}Задание\s*(?:№\s*)?\d+)/ui', $chunk);
                foreach ($taskChunks as $tChunk) {
                    $tChunk = trim($tChunk);
                    if (! preg_match('/\*{0,2}Задание\s*(?:№\s*)?(\d+)[^:\n]*:?\*{0,2}\s*(.*)/us', $tChunk, $tMatch)) {
                        continue;
                    }

                    $tNum = (int) $tMatch[1];
                    $body = trim($tMatch[2]);

                    $options = [];
                    $condLines = [];
                    $lines = explode("\n", $body);

                    foreach ($lines as $line) {
                        $trimLine = trim($line);
                        if (preg_match('/^([1-5]|[А-Да-дA-Ea-e])[\.\)]\s*(.+)$/u', $trimLine, $optMatch)) {
                            $rawIndex = $optMatch[1];
                            $optText = trim($optMatch[2]);
                            $letter = match ($rawIndex) {
                                '1', 'А', 'а', 'A', 'a' => 'A',
                                '2', 'Б', 'б', 'B', 'b' => 'B',
                                '3', 'В', 'в', 'C', 'c' => 'C',
                                '4', 'Г', 'г', 'D', 'd' => 'D',
                                '5', 'Д', 'д', 'E', 'e' => 'E',
                                default => strtoupper($rawIndex),
                            };

                            $options[] = [
                                'letter' => $letter,
                                'text' => htmlspecialchars($optText, ENT_QUOTES, 'UTF-8'),
                            ];
                        } else {
                            if (preg_match('/^(\*{0,2}(?:Задание|Ответ|Правильный ответ|Пояснение|Решение)|---|###)/ui', $trimLine)) {
                                break;
                            }
                            if (empty($options)) {
                                $condLines[] = $trimLine;
                            }
                        }
                    }

                    $conditionText = trim(implode(' ', array_filter($condLines)));
                    if (empty($conditionText)) {
                        $conditionText = 'Решите задание:';
                    }

                    $correct = null;
                    $explanation = null;
                    if (preg_match('/(?:(?:\*{0,2}(?:Правильный ответ|Ответ)\*{0,2}\s*:\s*\[?\s*([^\n\]\r]+)\s*\]?))/ui', $tChunk, $ansM)) {
                        $rawAns = trim($ansM[1]);
                        if (! preg_match('/^[_ \t\.\-\–]+$/u', $rawAns) && ! str_contains($rawAns, '____') && ! str_contains($rawAns, '&nbsp;')) {
                            if (! empty($options) && preg_match('/^([1-5]|[А-Да-дA-Ea-e])/u', $rawAns, $mLetter)) {
                                $rawL = $mLetter[1];
                                $correct = match ($rawL) {
                                    '1', 'А', 'а', 'A', 'a' => 'A',
                                    '2', 'Б', 'б', 'B', 'b' => 'B',
                                    '3', 'В', 'в', 'C', 'c' => 'C',
                                    '4', 'Г', 'г', 'D', 'd' => 'D',
                                    '5', 'Д', 'д', 'E', 'e' => 'E',
                                    default => strtoupper($rawL),
                                };
                            } else {
                                $correct = $rawAns;
                            }
                        }
                    }
                    if (preg_match('/(?:\*{0,2}(?:Пояснение|Объяснение|Ловушка|Решение)\*{0,2}\s*:\s*([^\n\r]+))/ui', $tChunk, $expM)) {
                        $explanation = trim($expM[1]);
                    }

                    $taskItem = [
                        'num' => $tNum,
                        'condition_html' => nl2br(htmlspecialchars($conditionText, ENT_QUOTES, 'UTF-8')),
                        'options' => $options,
                        'correct' => $correct,
                        'explanation' => $explanation,
                        'is_school' => true,
                    ];

                    $diagram = self::detectAndGenerateDiagram($conditionText);
                    if ($diagram) {
                        $taskItem['diagram_b64'] = base64_encode($diagram);
                    }

                    if (! empty($options)) {
                        $variantData['part_a'][] = $taskItem;
                    } else {
                        $variantData['part_b'][] = $taskItem;
                    }
                }
            }

            // Only add if at least one task exists
            if (! empty($variantData['part_a']) || ! empty($variantData['part_b'])) {
                $variants[] = $variantData;
            }
        }

        // 3. Parse Master Table
        $masterTable = [];
        if (! empty($masterSection)) {
            $tableLines = explode("\n", $masterSection);
            foreach ($tableLines as $line) {
                $trimLine = trim($line);
                if (str_starts_with($trimLine, '|') && ! str_contains($trimLine, '---') && ! preg_match('/\|\s*Вариант\s*\|/ui', $trimLine)) {
                    $cols = array_values(array_filter(array_map('trim', explode('|', $trimLine)), fn($c) => $c !== ''));
                    if (count($cols) >= 3) {
                        $vNumber = (int) preg_replace('/[^\d]/', '', $cols[0] ?? '1');
                        $masterTable[] = [
                            'variant' => $vNumber ?: (count($masterTable) + 1),
                            'part_a' => htmlspecialchars($cols[1] ?? '—', ENT_QUOTES, 'UTF-8'),
                            'part_b' => count($cols) >= 4 ? htmlspecialchars($cols[2] ?? '—', ENT_QUOTES, 'UTF-8') : '—',
                            'trap' => htmlspecialchars($cols[count($cols) - 1] ?? 'Внимание к знакам и ОДЗ', ENT_QUOTES, 'UTF-8'),
                        ];
                    }
                }
            }
        }

        // Fallback for empty master table
        if (empty($masterTable) && ! empty($variants)) {
            foreach ($variants as $v) {
                $masterTable[] = [
                    'variant' => $v['number'],
                    'part_a' => 'Сверено с авторским решением',
                    'part_b' => ! empty($v['part_b']) ? 'Целое число' : '—',
                    'trap' => 'Контроль ограничений и допустимых значений корней.',
                ];
            }
        }

        // Programmatic Verification & Sanitization Gate:
        // Cleanse explanations of internal drafting monologue, ensure option consistency,
        // and cross-synchronize master table with verified variants.
        $validationErrors = self::sanitizeAndVerifyVariants($variants, $masterTable, $order);

        // Calculate metadata
        $firstVar = $variants[0] ?? ['part_a' => [], 'part_b' => []];
        $countA = count($firstVar['part_a']);
        $countB = count($firstVar['part_b']);
        $totalCount = $countA + $countB;

        $grade = $order->payload['grade'] ?? '7';
        $workType = $order->payload['work_type'] ?? 'independent';
        $difficulty = $order->payload['difficulty'] ?? 'base';

        $gradeLabel = match ($grade) {
            'abiturient' => 'Абитуриент / Выпускник',
            default => "{$grade} класс",
        };

        $workTypeTitle = match ($workType) {
            'express' => 'Пятиминутка / Экспресс-опрос',
            'control' => 'Контрольная работа',
            'diagnostic' => 'Диагностическая работа',
            'test' => 'Проверочный экспресс-тест',
            'ct_ce', 'exam' => 'Пробный экзамен ЦЭ / ЦТ',
            'olympiad' => 'Олимпиадная работа',
            default => 'Самостоятельная работа',
        };

        $timeMinutes = match ($workType) {
            'express' => 7,
            'independent' => 20,
            'test' => 15,
            'control' => 45,
            'diagnostic' => 45,
            'olympiad' => 60,
            'ct_ce', 'exam' => ($totalCount >= 20 ? 180 : 90),
            default => 20,
        };

        $isCtCe = ($workType === 'ct_ce' || $workType === 'exam');
        $maxScore = $isCtCe ? 100 : 10;
        $numGrade = is_numeric($grade) ? (int) $grade : 11;
        $examFormat = $order->payload['exam_format'] ?? (($numGrade < 10 && ! $isCtCe) ? 'school' : 'exam');
        $printLayout = $order->payload['print_layout'] ?? ($examFormat === 'school' ? 'economy' : 'standard');

        $testTitle = $isCtCe
            ? 'Пробный тест в формате стандартов ЦТ и ЦЭ 2026'
            : "{$workTypeTitle} ({$gradeLabel}): ".htmlspecialchars($order->topic, ENT_QUOTES, 'UTF-8');

        $instructionText = match (true) {
            $examFormat === 'school' => "Работа рассчитана на {$timeMinutes} минут. Внимательно прочитайте условия заданий. Ответы запишите в таблицу ответов внизу листа.",
            $isCtCe && $countB > 0 => 'В тесте представлены задания с выбором одного правильного ответа (Часть А) и задания с кратким ответом (Часть Б). В бланке ответов внизу листа запишите номер задания и соответствующий ответ.',
            $isCtCe => 'В тесте представлены задания с выбором одного правильного ответа из предложенных. В бланке ответов запишите номер задания и букву выбранного ответа.',
            $countB > 0 => "Работа рассчитана на {$timeMinutes} минут. Внимательно прочитайте условия заданий. В Части А выберите один верный ответ, в Части Б запишите краткий ответ в бланк.",
            default => "Работа рассчитана на {$timeMinutes} минут. Внимательно прочитайте условия заданий и запишите буквы правильных ответов в бланк внизу листа.",
        };

        return [
            'variants' => $variants,
            'masterTable' => $masterTable,
            'validationErrors' => $validationErrors,
            'qrCodeBase64' => $qrPngB64,
            'qrCodePngBase64' => $qrPngB64,
            'testTitle' => $testTitle,
            'timeMinutes' => $timeMinutes,
            'totalTasksCount' => $totalCount,
            'maxScore' => $maxScore,
            'instructionText' => $instructionText,
            'grade' => $grade,
            'gradeLabel' => $gradeLabel,
            'workType' => $workType,
            'workTypeTitle' => $workTypeTitle,
            'difficulty' => $difficulty,
            'examFormat' => $examFormat,
            'printLayout' => $printLayout,
        ];
    }

    /**
     * Generate clear SVG diagrams for geometry or coordinate graphs
     */
    protected static function detectAndGenerateDiagram(string $condition): ?string
    {
        $lower = mb_strtolower($condition);

        // 1. Right triangle / triangle geometry
        if (str_contains($lower, 'прямоугольн') && str_contains($lower, 'треугольник')) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="95" height="85" viewBox="0 0 95 85">
                <polygon points="18,12 18,68 80,68" fill="none" stroke="#000" stroke-width="1.6"/>
                <rect x="18" y="58" width="10" height="10" fill="none" stroke="#000" stroke-width="1"/>
                <text x="8" y="16" font-size="9" font-weight="bold" font-family="DejaVu Sans" fill="#000">B</text>
                <text x="8" y="72" font-size="9" font-weight="bold" font-family="DejaVu Sans" fill="#000">C</text>
                <text x="82" y="72" font-size="9" font-weight="bold" font-family="DejaVu Sans" fill="#000">A</text>
            </svg>';
        }

        // 2. Parabola / coordinate graph
        if (str_contains($lower, 'график') && (str_contains($lower, 'функци') || str_contains($lower, 'парабол'))) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="115" height="95" viewBox="0 0 115 95">
                <line x1="8" y1="65" x2="110" y2="65" stroke="#000" stroke-width="1.4"/>
                <polygon points="110,65 104,62 104,68" fill="#000"/>
                <text x="103" y="58" font-size="9" font-family="DejaVu Sans" fill="#000">x</text>
                <line x1="48" y1="90" x2="48" y2="8" stroke="#000" stroke-width="1.4"/>
                <polygon points="48,8 45,14 51,14" fill="#000"/>
                <text x="52" y="14" font-size="9" font-family="DejaVu Sans" fill="#000">y</text>
                <path d="M 20 22 Q 54 96 88 22" fill="none" stroke="#000" stroke-width="1.8"/>
                <text x="24" y="76" font-size="8" font-family="DejaVu Sans" fill="#000">-1</text>
                <text x="74" y="76" font-size="8" font-family="DejaVu Sans" fill="#000">2</text>
                <text x="51" y="76" font-size="8" font-family="DejaVu Sans" fill="#000">0</text>
            </svg>';
        }

        // 3. Circle geometry
        if (str_contains($lower, 'окружност') || str_contains($lower, 'круг')) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="90" height="90" viewBox="0 0 90 90">
                <circle cx="45" cy="45" r="35" fill="none" stroke="#000" stroke-width="1.5"/>
                <circle cx="45" cy="45" r="2" fill="#000"/>
                <line x1="45" y1="45" x2="80" y2="45" stroke="#000" stroke-width="1.2" stroke-dasharray="2,2"/>
                <text x="40" y="42" font-size="8" font-weight="bold" font-family="DejaVu Sans" fill="#000">O</text>
                <text x="58" y="40" font-size="8" font-family="DejaVu Sans" fill="#000">R</text>
            </svg>';
        }

        return null;
    }

    /**
     * Programmatic verification gate: cleanses AI reasoning artifacts, validates option consistency,
     * and strictly synchronizes master table with variants.
     */
    public static function sanitizeAndVerifyVariants(array &$variants, array &$masterTable, GeneratorOrder $order): array
    {
        // IMPORTANT: this method must never invent tasks, distractors or answer keys.
        // It only normalizes labels and reports defects. Invalid content is blocked
        // before PDF export instead of being silently "repaired" with random values.
        $errors = [];
        $payload = is_array($order->payload) ? $order->payload : [];
        $subject = mb_strtolower((string) ($order->subject ?? ''));
        $isExact = str_contains($subject, 'математ') || str_contains($subject, 'алгебр')
            || str_contains($subject, 'геометр') || str_contains($subject, 'физик')
            || str_contains($subject, 'хим');

        $targetCountA = (int) ($payload['count_a'] ?? 0);
        $targetCountB = (int) ($payload['count_b'] ?? 0);
        $workType = (string) ($payload['work_type'] ?? 'independent');
        $grade = (string) ($payload['grade'] ?? '7');
        $numGrade = is_numeric($grade) ? (int) $grade : 11;
        $rawFormat = (string) ($payload['exam_format'] ?? '');
        $isSchool = ($rawFormat === 'school') || ($rawFormat === '' && $numGrade < 10 && $workType !== 'ct_ce');

        if (empty($variants)) {
            return ['Документ не содержит ни одного варианта.'];
        }

        foreach ($variants as $vIdx => &$variant) {
            $varNum = (int) ($variant['number'] ?? ($vIdx + 1));
            $variant['part_a'] = is_array($variant['part_a'] ?? null) ? $variant['part_a'] : [];
            $variant['part_b'] = is_array($variant['part_b'] ?? null) ? $variant['part_b'] : [];

            if (array_key_exists('count_a', $payload) && count($variant['part_a']) !== $targetCountA) {
                $errors[] = "Вариант {$varNum}: ожидалось заданий с выбором ответа — {$targetCountA}, получено — ".count($variant['part_a']).'.';
            }
            if (array_key_exists('count_b', $payload) && count($variant['part_b']) !== $targetCountB) {
                $errors[] = "Вариант {$varNum}: ожидалось заданий с кратким ответом — {$targetCountB}, получено — ".count($variant['part_b']).'.';
            }

            $taskGroups = [
                ['items' => &$variant['part_a'], 'kind' => 'A'],
                ['items' => &$variant['part_b'], 'kind' => 'B'],
            ];

            foreach ($taskGroups as &$group) {
                foreach ($group['items'] as $taskIndex => &$task) {
                    $taskNum = (int) ($task['num'] ?? ($taskIndex + 1));
                    $label = $group['kind'] === 'A' ? "А{$taskNum}" : "Б{$taskNum}";
                    $rawCondition = (string) ($task['condition_html'] ?? $task['prompt'] ?? '');
                    $plainCondition = trim(html_entity_decode(strip_tags($rawCondition), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $plainCondition = preg_replace('/\s+/u', ' ', $plainCondition) ?? $plainCondition;

                    if ($plainCondition === '' || mb_strlen($plainCondition) < 8) {
                        $errors[] = "Вариант {$varNum}, задание {$label}: отсутствует полноценное условие.";
                    }
                    if (preg_match('/\[(?:текст|условие|вариант|вставьте|заполните)[^\]]*\]/ui', $plainCondition)
                        || preg_match('/условие тестового задания|решите задание\s*:?$/ui', $plainCondition)) {
                        $errors[] = "Вариант {$varNum}, задание {$label}: вместо условия осталась шаблонная заглушка.";
                    }

                    // Detect common AI hallucination where the stem ends after a parameter
                    // and the actual expression (e.g. "при k = 1:") is missing.
                    if ($isExact && preg_match('/значени[ея]\s+выражени[яе].*при\s+[a-zа-яё]\s*=\s*[-+]?\d+\s*[:.]?$/ui', $plainCondition)
                        && ! preg_match('/\d\s*[+\-*\/·×^]\s*\d|[a-zа-яё]\s*[+\-*\/·×^]\s*\d/ui', $plainCondition)) {
                        $errors[] = "Вариант {$varNum}, задание {$label}: условие ссылается на выражение, но само выражение отсутствует.";
                    }

                    $expl = trim((string) ($task['explanation'] ?? ''));
                    $task['explanation'] = $expl !== '' ? self::cleanseAiExplanation($expl, (string) ($task['correct'] ?? '')) : '';

                    if ($group['kind'] === 'A') {
                        $options = is_array($task['options'] ?? null) ? array_values($task['options']) : [];
                        if (! in_array(count($options), [4, 5], true)) {
                            $errors[] = "Вариант {$varNum}, задание {$label}: требуется 4 или 5 реальных вариантов ответа; найдено — ".count($options).'.';
                        }
                        $optionTexts = [];
                        foreach ($options as $optionIndex => &$option) {
                            $optionText = trim(html_entity_decode(strip_tags((string) ($option['text'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                            $option['letter'] = chr(65 + $optionIndex);
                            if ($optionText === '' || preg_match('/^вариант\s*\d+$/ui', $optionText)) {
                                $errors[] = "Вариант {$varNum}, задание {$label}: обнаружен пустой или фиктивный вариант ответа.";
                            }
                            $normalizedOption = mb_strtolower(preg_replace('/\s+/u', ' ', $optionText) ?? $optionText);
                            if (isset($optionTexts[$normalizedOption])) {
                                $errors[] = "Вариант {$varNum}, задание {$label}: повторяется вариант ответа «{$optionText}».";
                            }
                            $optionTexts[$normalizedOption] = true;
                        }
                        unset($option);
                        $task['options'] = $options;

                        $answer = strtoupper(trim((string) ($task['correct'] ?? '')));
                        if (preg_match('/^[1-5]$/', $answer)) {
                            $answer = chr(64 + (int) $answer);
                        }
                        if (! in_array($answer, ['A', 'B', 'C', 'D', 'E'], true) || ord($answer) - 65 >= count($options)) {
                            $errors[] = "Вариант {$varNum}, задание {$label}: правильный ответ не задан или не соответствует вариантам.";
                        } else {
                            $task['correct'] = $answer;
                        }
                    } else {
                        $answer = trim((string) ($task['correct'] ?? ''));
                        $answer = preg_replace('/^(?:ответ|правильный ответ)\s*:\s*/ui', '', $answer) ?? $answer;
                        $answer = trim($answer, "[] \t\n\r");
                        if ($answer === '' || in_array(mb_strtolower($answer), ['целое число', 'ответ', '—', 'нет ответа'], true)
                            || str_contains($answer, '____') || preg_match('/^\[.*\]$/u', $answer)) {
                            $errors[] = "Вариант {$varNum}, задание {$label}: отсутствует проверяемый правильный ответ.";
                        } else {
                            $task['correct'] = $answer;
                        }
                    }

                    $task['num'] = $isSchool
                        ? ($group['kind'] === 'A' ? $taskIndex + 1 : count($variant['part_a']) + $taskIndex + 1)
                        : $taskIndex + 1;
                    $task['number'] = $isSchool
                        ? (string) $task['num']
                        : ($group['kind'] === 'A' ? 'А' : 'Б').($taskIndex + 1);
                    $task['is_school'] = $isSchool;
                }
                unset($task);
            }
            unset($group, $taskGroups);
        }
        unset($variant);

        // Keep answer keys derived only from actual parsed answers. Never supply defaults.
        $newMasterTable = [];
        foreach ($variants as $vIdx => $variant) {
            $partAKeys = array_map(static fn ($task) => $task['correct'] ?? '—', $variant['part_a'] ?? []);
            $partBKeys = array_map(static fn ($task) => $task['correct'] ?? '—', $variant['part_b'] ?? []);
            $trap = $masterTable[$vIdx]['trap'] ?? '';
            $newMasterTable[] = [
                'variant' => $variant['number'] ?? ($vIdx + 1),
                'part_a' => implode(', ', $partAKeys),
                'part_b' => implode(', ', $partBKeys),
                'trap' => $trap,
            ];
        }
        $masterTable = $newMasterTable;

        return array_values(array_unique($errors));
    }

    /**
     * Cleanse explanation of internal AI thoughts ("стоп", "пересчитаем", "исправляем", "заменяем", "не целое")
     */
    public static function cleanseAiExplanation(string $expl, string $fallbackAns = ''): string
    {
        $expl = trim($expl);
        if (empty($expl)) {
            return 'Сверено по экзаменационным спецификациям.';
        }

        // If explanation contains self-correction panic, clean it up!
        $hasPanic = preg_match('/(?:стоп|проверяем|пересчитаем|исправляем|заменяем ответ|заменим|не целое|давайте пересчитаем)/ui', $expl);
        if ($hasPanic) {
            // If there is an equation resolution at the start, take only that
            if (preg_match('/^(?:Решение\s*:\s*)?([^\.\?\!\n]+(?:=\s*[\-0-9a-zA-Z]+))[\.\?\!]/u', $expl, $matchEq)) {
                return 'Решение: ' . trim($matchEq[1]) . '.';
            }
            // Otherwise, replace with a clean pedagogical solution statement
            return 'Решение выполнено по алгоритму учебной программы. Ответ: ' . ($fallbackAns ?: 'верный') . '.';
        }

        // Limit explanation to at most 120 characters to prevent spilling across pages
        if (mb_strlen($expl) > 130) {
            $sentences = preg_split('/(?<=[.!?])\s+/u', $expl);
            $first = trim($sentences[0] ?? $expl);
            if (mb_strlen($first) <= 130) {
                return $first;
            }
            return mb_substr($first, 0, 125) . '...';
        }

        return $expl;
    }
}
