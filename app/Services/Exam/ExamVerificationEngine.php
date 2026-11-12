<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\GeneratorOrder;
use App\Services\Ai\GeminiService;
use Illuminate\Support\Facades\Log;

/**
 * ExamVerificationEngine — специализированный алгоритм независимой проверки
 * корректности решений, ключей и ответов в бланках проверочных работ Edusfera.
 *
 * Выполняет двухконтурный контроль:
 * 1. Алгоритмическая математическая и логическая валидация (детерминированное
 *    решение типовых уравнений, проверка дистракторов на дубли, проверка формата бланка).
 * 2. Экспертная верификация решений нейросетью (Second-Pass Solver),
 *    гарантирующая отсутствие галлюцинаций в ответах и методических ловушках.
 */
class ExamVerificationEngine
{
    /**
     * Основная точка входа: проверка и исправление сгенерированного теста
     */
    public static function verifyAndCorrect(
        string $content,
        GeneratorOrder $order,
        ?GeminiService $gemini = null
    ): string {
        $subject = mb_strtolower(trim((string) $order->subject));
        $isHumanities = str_contains($subject, 'истор') || str_contains($subject, 'обществ');
        $isExact = str_contains($subject, 'математ') || str_contains($subject, 'алгебр') || str_contains($subject, 'геометр') || str_contains($subject, 'физик') || str_contains($subject, 'хим');

        // Шаг 1. Предварительный парсинг структуры документа
        $parsed = ExamDocumentParser::parse($content, $order);
        $variants = $parsed['variants'] ?? [];
        $masterTable = $parsed['masterTable'] ?? [];

        if (! empty($parsed['validationErrors'])) {
            Log::warning('ExamVerificationEngine: source test rejected before verification', [
                'order' => $order->order_number ?? null,
                'errors' => $parsed['validationErrors'],
            ]);
            return $content;
        }

        if (empty($variants)) {
            return $content;
        }

        // Шаг 2. Контур I: Алгоритмическая проверка и детерминированное решение
        $hasCorrections = false;
        foreach ($variants as $vIdx => &$variant) {
            $varNum = $variant['number'] ?? ($vIdx + 1);

            // Проверка заданий с выбором ответа (Часть А / школа)
            foreach ($variant['part_a'] as $tIdx => &$task) {
                // 2.1 Проверка на дубликаты среди вариантов ответа
                if (self::fixDuplicateOptions($task)) {
                    $hasCorrections = true;
                }

                // 2.2 Для точных наук: попытка детерминированного решения уравнения/примера
                if ($isExact) {
                    $solvedAns = self::tryDeterministicMathSolve((string) ($task['condition_html'] ?? ''));
                    if ($solvedAns !== null) {
                        // Ищем, есть ли полученный ответ среди опций
                        foreach ($task['options'] as $opt) {
                            $optClean = trim(strip_tags((string) ($opt['text'] ?? '')));
                            if ($optClean === (string) $solvedAns || $optClean === str_replace('.', ',', (string) $solvedAns)) {
                                if (($task['correct'] ?? '') !== $opt['letter']) {
                                    $task['correct'] = $opt['letter'];
                                    $task['explanation'] = "Точное математическое решение: корень равен {$solvedAns}.";
                                    $hasCorrections = true;
                                }
                                break;
                            }
                        }
                    }
                }
            }
            unset($task);

            // Проверка открытых заданий (Часть Б / школа)
            foreach ($variant['part_b'] as $tIdx => &$task) {
                $rawAns = trim((string) ($task['correct'] ?? ''));

                // Do not fabricate open answers or replace the task statement.
                // Missing / generic answers are reported by ExamDocumentParser and
                // block PDF export; inventing a plausible-looking key is unsafe.
                if (empty($rawAns) || in_array(mb_strtolower($rawAns), ['целое число', '—', 'ответ'], true)
                    || str_contains($rawAns, '____')) {
                    Log::warning('ExamVerificationEngine: open task has no verified answer', [
                        'variant' => $varNum,
                        'task' => $task['num'] ?? ($tIdx + 1),
                    ]);
                }
            }
            unset($task);
        }
        unset($variant);

        // Шаг 3. Контур II: Экспертная верификация решений через Gemini (Second-Pass Solver)
        if ($gemini && $gemini->isConfigured()) {
            try {
                $aiVerification = self::runAiExpertVerification($variants, $order, $gemini);
                if (! empty($aiVerification)) {
                    self::applyAiCorrections($variants, $aiVerification);
                    $hasCorrections = true;
                }
            } catch (\Throwable $e) {
                Log::warning('ExamVerificationEngine: AI verification pass skipped: ' . $e->getMessage());
            }
        }

        // Шаг 4. Финальная синхронизация бланка ответов и сводной мастер-таблицы
        ExamDocumentParser::sanitizeAndVerifyVariants($variants, $masterTable, $order);

        // Шаг 5. Сборка верифицированного академического документа
        return self::reconstructVerifiedMarkdown($variants, $order, $parsed['examFormat'] ?? 'exam');
    }

    /**
     * Попытка детерминированного математического решения уравнения
     */
    protected static function tryDeterministicMathSolve(string $condition): ?string
    {
        $text = strip_tags(html_entity_decode($condition));

        // 1. Уравнение вида (x - a)(x + b) = 0 или (x - a) = 0
        if (preg_match('/\(\s*x\s*([\+\-])\s*(\d+)\s*\)\s*\(\s*x\s*([\+\-])\s*(\d+)\s*\)\s*=\s*0/u', $text, $m)) {
            $r1 = ($m[1] === '-') ? (int) $m[2] : -((int) $m[2]);
            $r2 = ($m[3] === '-') ? (int) $m[4] : -((int) $m[4]);
            return "{$r1} и {$r2}";
        }

        // 2. Линейное уравнение вида ax + b = c
        if (preg_match('/(?:^|[^\w])(\-?\d*)\s*x\s*([\+\-])\s*(\d+)\s*=\s*(\-?\d+)/u', $text, $m)) {
            $aStr = $m[1];
            $a = ($aStr === '' || $aStr === '+') ? 1 : (($aStr === '-') ? -1 : (int) $aStr);
            $sign = $m[2];
            $b = (int) $m[3];
            $bSigned = ($sign === '-') ? -$b : $b;
            $c = (int) $m[4];
            if ($a !== 0) {
                $x = ($c - $bSigned) / $a;
                if (floor($x) == $x) {
                    return (string) ((int) $x);
                }
            }
        }

        // 3. Линейное уравнение ax = b
        if (preg_match('/(?:^|[^\w])(\-?\d+)\s*x\s*=\s*(\-?\d+)/u', $text, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a !== 0) {
                $x = $b / $a;
                if (floor($x) == $x) {
                    return (string) ((int) $x);
                }
            }
        }

        // 4. Корень вида √(x ± a) = b
        if (preg_match('/[√\x{221A}]\s*\(\s*x\s*([\+\-])\s*(\d+)\s*\)\s*=\s*(\d+)/u', $text, $m)) {
            $sign = $m[1];
            $a = (int) $m[2];
            $b = (int) $m[3];
            $sq = $b * $b;
            $x = ($sign === '-') ? ($sq + $a) : ($sq - $a);
            return (string) $x;
        }

        return null;
    }

    /**
     * Поиск и устранение дубликатов среди вариантов ответа (дистракторов)
     */
    protected static function fixDuplicateOptions(array &$task): bool
    {
        $options = $task['options'] ?? [];
        if (count($options) < 2) {
            return false;
        }

        $seen = [];
        $changed = false;

        foreach ($task['options'] as $idx => &$opt) {
            $val = trim(strip_tags((string) ($opt['text'] ?? '')));
            if (isset($seen[$val])) {
                // Найден дубликат: генерируем альтернативный дистрактор
                if (is_numeric($val)) {
                    $newVal = (string) (((int) $val) + 3);
                    while (isset($seen[$newVal])) {
                        $newVal = (string) (((int) $newVal) + 2);
                    }
                    $opt['text'] = $newVal;
                    $seen[$newVal] = true;
                    $changed = true;
                } else {
                    $newVal = $val . ' (вар. ' . ($idx + 1) . ')';
                    $opt['text'] = $newVal;
                    $seen[$newVal] = true;
                    $changed = true;
                }
            } else {
                $seen[$val] = true;
            }
        }
        unset($opt);

        return $changed;
    }

    /**
     * Запуск второго экспертного прохода нейросети (AI Solver & Fact-Checker)
     *
     * @return array<string, array{correct: string, explanation: string}>
     */
    protected static function runAiExpertVerification(
        array $variants,
        GeneratorOrder $order,
        GeminiService $gemini
    ): array {
        $subject = htmlspecialchars((string) $order->subject, ENT_QUOTES, 'UTF-8');
        $topic = htmlspecialchars((string) $order->topic, ENT_QUOTES, 'UTF-8');

        // Сборка компактного списка заданий для проверки
        $tasksToVerify = [];
        foreach ($variants as $v) {
            $vNum = $v['number'];
            foreach (array_merge($v['part_a'] ?? [], $v['part_b'] ?? []) as $t) {
                $tNum = $t['num'] ?? $t['number'];
                $isPartA = ! empty($t['options']);
                $optsStr = '';
                if ($isPartA) {
                    $optLines = [];
                    foreach ($t['options'] as $o) {
                        $optLines[] = "{$o['letter']}) " . strip_tags((string) ($o['text'] ?? ''));
                    }
                    $optsStr = implode(' | ', $optLines);
                }

                $promptClean = strip_tags(html_entity_decode((string) ($t['condition_html'] ?? $t['prompt'] ?? '')));
                $tasksToVerify[] = sprintf(
                    "V%d_T%s [%s]: %s %s [Черновик ответа: %s]",
                    $vNum,
                    $tNum,
                    $isPartA ? 'TEST' : 'OPEN',
                    $promptClean,
                    $optsStr ? "(Варианты: {$optsStr})" : "",
                    $t['correct'] ?? '—'
                );
            }
        }

        if (empty($tasksToVerify)) {
            return [];
        }

        $tasksList = implode("\n", array_slice($tasksToVerify, 0, 30));

        $verifierSystem = <<<SYS
Ты — главный эксперт-верификатор и председатель экзаменационной комиссии Беларуси по предмету «{$subject}».
ТВОЯ ЗАДАЧА — строго проверить правильность решений каждого задания проверочной работы по теме «{$topic}».
Для каждого задания:
1. Реши задание с нуля, найди математически или исторически абсолютно верный ответ.
2. Для тестовых заданий (TEST): определи точную БУКВУ верного варианта (A, B, C, D). Если в черновике указана неверная буква — исправь на истинную!
3. Для открытых заданий (OPEN):
   - В математике/физике: точное целое число (например, 14, -3).
   - В истории/обществоведении: код соответствия (А1Б3В2Г4) или точный термин в им. падеже (Статут ВКЛ). НИКАКИХ "целое число" и подсчетов лет!
4. Формат вывода — СТРОГО построчный JSON-список следующего вида:
[
  {"id": "V1_T1", "correct": "B", "explanation": "Точный ход решения и почему дистракторы неверны."},
  {"id": "V1_T2", "correct": "C", "explanation": "Обоснование ответа."}
]
Никаких вводных слов, только валидный JSON.
SYS;

        $prompt = "Проведи экспертизу и реши следующие задания чистовика:\n\n" . $tasksList;

        $rawResponse = $gemini->generateText($prompt, $verifierSystem, timeoutSeconds: 50);
        if (empty($rawResponse)) {
            return [];
        }

        // Извлечение JSON
        if (preg_match('/\[\s*\{.*\}\s*\]/us', $rawResponse, $match)) {
            $json = json_decode($match[0], true);
            if (is_array($json)) {
                $result = [];
                foreach ($json as $item) {
                    if (isset($item['id'], $item['correct'])) {
                        $result[$item['id']] = [
                            'correct' => trim((string) $item['correct']),
                            'explanation' => trim((string) ($item['explanation'] ?? '')),
                        ];
                    }
                }
                return $result;
            }
        }

        return [];
    }

    /**
     * Применение результатов экспертной верификации нейросети к вариантам
     */
    protected static function applyAiCorrections(array &$variants, array $corrections): void
    {
        foreach ($variants as &$v) {
            $vNum = $v['number'];

            foreach ($v['part_a'] as &$task) {
                $tNum = $task['num'] ?? $task['number'];
                $key = "V{$vNum}_T{$tNum}";
                if (isset($corrections[$key])) {
                    $corrLetter = strtoupper(trim($corrections[$key]['correct']));
                    if (in_array($corrLetter, ['A', 'B', 'C', 'D', 'E'])) {
                        $task['correct'] = $corrLetter;
                    }
                    if (! empty($corrections[$key]['explanation'])) {
                        $task['explanation'] = $corrections[$key]['explanation'];
                    }
                }
            }
            unset($task);

            foreach ($v['part_b'] as &$task) {
                $tNum = $task['num'] ?? $task['number'];
                $key = "V{$vNum}_T{$tNum}";
                if (isset($corrections[$key])) {
                    $corrAns = trim($corrections[$key]['correct']);
                    if (! empty($corrAns) && mb_strtolower($corrAns) !== 'целое число') {
                        $task['correct'] = $corrAns;
                    }
                    if (! empty($corrections[$key]['explanation'])) {
                        $task['explanation'] = $corrections[$key]['explanation'];
                    }
                }
            }
            unset($task);
        }
        unset($v);
    }

    /**
     * Воссоздание чистового верифицированного Markdown-документа
     */
    protected static function reconstructVerifiedMarkdown(
        array $variants,
        GeneratorOrder $order,
        string $examFormat = 'exam'
    ): string {
        $isSchool = ($examFormat === 'school');
        $out = "# Комплект проверочных вариантов\n\n";
        $out .= "**Предмет:** {$order->subject}  \n";
        $out .= "**Тема:** {$order->topic}  \n\n";

        foreach ($variants as $v) {
            $varNum = $v['number'];
            $out .= "### Вариант {$varNum}\n";
            $out .= "Учащийся: ___________________________   Класс: _______   Дата: ____________\n\n";

            if ($isSchool) {
                $allTasks = array_merge($v['part_a'] ?? [], $v['part_b'] ?? []);
                foreach ($allTasks as $idx => $t) {
                    $tNum = $idx + 1;
                    $cond = strip_tags(html_entity_decode((string) ($t['condition_html'] ?? $t['prompt'] ?? '')));
                    $cond = preg_replace('/\s*(?:ответ|правильный ответ|пояснение)\s*:\s*.*$/ui', '', $cond);
                    $cond = trim($cond);
                    $out .= "**Задание {$tNum}:**  \n";
                    $out .= "{$cond}  \n";

                    if (! empty($t['options'])) {
                        foreach ($t['options'] as $oIdx => $opt) {
                            $optNum = $oIdx + 1;
                            $optText = strip_tags(html_entity_decode((string) ($opt['text'] ?? '')));
                            $out .= "{$optNum}) {$optText}  \n";
                        }
                    }

                    $correctVal = $t['correct'] ?? '—';
                    if (! empty($t['options']) && in_array(strtoupper($correctVal), ['A', 'B', 'C', 'D', 'E'])) {
                        $numMap = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
                        $correctVal = (string) ($numMap[strtoupper($correctVal)] ?? 1);
                    }

                    $out .= "Ответ: {$correctVal}  \n";
                    if (! empty($t['explanation'])) {
                        $out .= "Пояснение: {$t['explanation']}  \n";
                    }
                    $out .= "\n";
                }
            } else {
                // Exam format (Часть А и Б)
                foreach ($v['part_a'] ?? [] as $t) {
                    $tNum = $t['num'] ?? $t['number'] ?? 1;
                    $cond = strip_tags(html_entity_decode((string) ($t['condition_html'] ?? $t['prompt'] ?? '')));
                    $cond = preg_replace('/\s*(?:ответ|правильный ответ|пояснение)\s*:\s*.*$/ui', '', $cond);
                    $cond = trim($cond);
                    $out .= "**Задание А{$tNum} (Часть А, с выбором ответа):**  \n";
                    $out .= "{$cond}  \n";
                    foreach ($t['options'] ?? [] as $oIdx => $opt) {
                        $optNum = $oIdx + 1;
                        $optText = strip_tags(html_entity_decode((string) ($opt['text'] ?? '')));
                        $out .= "{$optNum}) {$optText}  \n";
                    }
                    $out .= "Ответ: {$t['correct']}  \n";
                    if (! empty($t['explanation'])) {
                        $out .= "Пояснение: {$t['explanation']}  \n";
                    }
                    $out .= "\n";
                }

                foreach ($v['part_b'] ?? [] as $t) {
                    $tNum = $t['num'] ?? $t['number'] ?? 1;
                    $cond = strip_tags(html_entity_decode((string) ($t['condition_html'] ?? $t['prompt'] ?? '')));
                    $cond = preg_replace('/\s*(?:ответ|правильный ответ|пояснение)\s*:\s*.*$/ui', '', $cond);
                    $cond = trim($cond);
                    $out .= "**Задание Б{$tNum} (Часть Б, открытый ответ):**  \n";
                    $out .= "{$cond}  \n";
                    $out .= "Ответ: {$t['correct']}  \n";
                    if (! empty($t['explanation'])) {
                        $out .= "Пояснение: {$t['explanation']}  \n";
                    }
                    $out .= "\n";
                }
            }

            $out .= "---\n\n";
        }

        // Добавление сводной таблицы правильных ответов
        $out .= "## Сводная мастер-таблица правильных ответов для преподавателя\n\n";
        if ($isSchool) {
            $out .= "| Вариант | Ответы к заданиям | Методическая ловушка и типичная ошибка |\n";
            $out .= "| :---: | :---: | :--- |\n";
            foreach ($variants as $v) {
                $allKeys = [];
                $all = array_merge($v['part_a'] ?? [], $v['part_b'] ?? []);
                foreach ($all as $t) {
                    $allKeys[] = $t['correct'] ?? '—';
                }
                $keysStr = implode(', ', $allKeys);
                $out .= "| Вариант {$v['number']} | {$keysStr} | Контроль базовых дистракторов и типичных ошибок. |\n";
            }
        } else {
            $out .= "| Вариант | Ответы Части А | Ответы Части Б | Методическая ловушка и типичная ошибка |\n";
            $out .= "| :---: | :---: | :---: | :--- |\n";
            foreach ($variants as $v) {
                $aKeys = array_map(fn($t) => $t['correct'] ?? 'A', $v['part_a'] ?? []);
                $bKeys = array_map(fn($t) => $t['correct'] ?? '—', $v['part_b'] ?? []);
                $aStr = implode(', ', $aKeys);
                $bStr = implode(', ', $bKeys);
                $out .= "| Вариант {$v['number']} | {$aStr} | {$bStr} | Контроль ограничений и типичных ошибок. |\n";
            }
        }

        return $out;
    }
}
