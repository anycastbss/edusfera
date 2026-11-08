<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>{{ $order->subject ?? 'Математика' }} — {{ $order->topic ?? 'Проверочная работа' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 10mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.38;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
        }

        /* ── Individual Student Variant Sheet ── */
        .exam-sheet {
            page-break-after: always;
            position: relative;
        }
        .exam-sheet:last-of-type {
            page-break-after: auto;
        }

        /* ── Header 1: Logo & Brand ── */
        .brand-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .brand-col {
            vertical-align: middle;
        }
        .logo-box {
            display: inline-block;
            vertical-align: middle;
        }
        .brand-name {
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #000000;
            line-height: 1;
            margin-left: 6px;
            display: inline-block;
            vertical-align: middle;
        }
        .brand-desc {
            font-size: 7pt;
            color: #333333;
            margin-top: 3px;
            margin-left: 36px;
            font-weight: normal;
        }
        .brand-divider {
            color: #888888;
            font-size: 15pt;
            margin: 0 10px;
            vertical-align: middle;
            font-weight: 300;
        }
        .brand-slogan {
            font-style: italic;
            font-size: 10.5pt;
            color: #111111;
            vertical-align: middle;
            font-weight: normal;
        }

        .qr-card {
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 3px 6px;
            float: right;
            background: #ffffff;
        }
        .qr-text {
            font-size: 7pt;
            line-height: 1.25;
            color: #000000;
            padding-right: 6px;
            vertical-align: middle;
            text-align: left;
        }
        .qr-img {
            display: block;
            vertical-align: middle;
        }

        /* ── Header 2: Passport of Test ── */
        .passport-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 8px;
        }
        .passport-title {
            font-size: 26pt;
            font-weight: bold;
            line-height: 1.05;
            color: #000000;
            letter-spacing: -0.5px;
        }
        .passport-subtitle {
            font-size: 10.5pt;
            font-weight: bold;
            color: #27272a;
            margin-top: 4px;
        }
        .passport-badge-box {
            background-color: #f4f4f5;
            border: 1px solid #d4d4d8;
            border-radius: 6px;
            padding: 5px 8px;
            float: right;
            text-align: left;
        }
        .passport-meta-table {
            border-collapse: collapse;
            font-size: 7.5pt;
            line-height: 1.35;
            color: #18181b;
        }
        .passport-meta-table td {
            vertical-align: middle;
            padding: 1px 2px;
        }

        .black-rule {
            border-bottom: 1.5px solid #000000;
            margin-bottom: 8px;
        }

        /* ── Instructions Callout ── */
        .instruction-box {
            border: 1.2px solid #000000;
            border-radius: 5px;
            padding: 5px 8px;
            margin-bottom: 8px;
            font-size: 7.8pt;
            line-height: 1.32;
            background: #ffffff;
            page-break-inside: avoid;
        }
        .info-circle {
            border: 1.2px solid #000000;
            border-radius: 50%;
            width: 13px;
            height: 13px;
            text-align: center;
            line-height: 12px;
            font-size: 7.5pt;
            font-weight: bold;
            display: inline-block;
            margin-right: 4px;
        }

        /* ── Section Header Bars ── */
        .section-bar {
            width: 100%;
            border-collapse: collapse;
            background-color: #f4f4f5;
            border-radius: 4px;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        .section-bar td {
            padding: 3px 6px;
            vertical-align: middle;
        }
        .section-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000000;
        }
        .section-pill {
            background: #ffffff;
            border: 1px solid #d4d4d8;
            border-radius: 3px;
            padding: 1px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #18181b;
            float: right;
        }

        /* ── Task Items ── */
        .task-item {
            page-break-inside: avoid;
            border-bottom: 1px solid #e4e4e7;
            padding: 6px 0 8px 0;
        }
        .task-table {
            width: 100%;
            border-collapse: collapse;
        }
        .task-num-col {
            width: 42px;
            vertical-align: top;
            padding-right: 6px;
        }
        .task-badge {
            border: 1.4px solid #000000;
            border-radius: 4px;
            text-align: center;
            padding: 2px 0;
            font-size: 8.5pt;
            font-weight: bold;
            color: #000000;
            width: 36px;
            background: #ffffff;
        }
        .task-content-col {
            vertical-align: top;
        }
        .task-condition {
            font-size: 9pt;
            line-height: 1.4;
            color: #000000;
            margin-bottom: 6px;
        }
        .task-diagram-col {
            width: 120px;
            vertical-align: middle;
            text-align: right;
            padding-left: 6px;
        }

        /* ── Part A: Options Row ── */
        .options-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .options-table td {
            vertical-align: middle;
            padding: 2px 4px 2px 0;
            white-space: nowrap;
        }
        .opt-box {
            border: 1.2px solid #000000;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7.5pt;
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 15px;
            text-align: center;
            margin-right: 3px;
            background: #ffffff;
        }
        .opt-value {
            font-size: 8.5pt;
            color: #000000;
        }

        /* ── Part B: Answer Form Cells ── */
        .ans-cells-table {
            border-collapse: collapse;
            margin-top: 4px;
        }
        .ans-label {
            font-size: 8.5pt;
            font-weight: bold;
            padding-right: 6px;
            vertical-align: middle;
        }
        .exam-cell {
            width: 18px;
            height: 20px;
            line-height: 20px;
            border: 1.2px solid #000000;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
            display: inline-block;
            margin-right: 2px;
            background: #ffffff;
            vertical-align: middle;
        }
        .exam-cell.sign-cell {
            background: #f4f4f5;
            color: #555555;
            font-size: 8pt;
        }
        .ans-hint {
            font-size: 6.8pt;
            color: #71717a;
            padding-left: 8px;
            vertical-align: middle;
        }

        /* ── Bottom Examination Answer Grid ── */
        .answer-grid-container {
            border-top: 1.5px dashed #000000;
            margin-top: 10px;
            padding-top: 6px;
            page-break-inside: avoid;
        }
        .grid-wrapper-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-label-col {
            width: 140px;
            vertical-align: middle;
        }
        .grid-table {
            border-collapse: collapse;
            margin-left: auto;
        }
        .grid-table th {
            font-size: 7.5pt;
            font-weight: bold;
            background: #f4f4f5;
            border: 1.2px solid #000000;
            text-align: center;
            padding: 2px 2px;
            height: 18px;
        }
        .grid-table td {
            border: 1.2px solid #000000;
            background: #ffffff;
            height: 22px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }

        /* ── Teacher Master Sheet ── */
        .teacher-sheet {
            page-break-before: always;
            page-break-inside: auto;
            padding-top: 4px;
        }
        .teacher-header-box {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .teacher-badge {
            background-color: #000000;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            padding: 2px 8px;
            display: inline-block;
            letter-spacing: 0.8px;
            border-radius: 2px;
            margin-bottom: 4px;
        }
        .teacher-title {
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
        }
        .teacher-sub {
            font-size: 8pt;
            color: #444444;
            margin-top: 1px;
        }
        .master-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 7.5pt;
        }
        .master-table th {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            padding: 4px 6px;
            border: 1px solid #000000;
            text-align: left;
        }
        .master-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            line-height: 1.35;
        }
        .master-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .grading-box {
            border: 1.2px solid #000000;
            padding: 5px 8px;
            margin-top: 8px;
            background-color: #fafafa;
            page-break-inside: avoid;
        }
        .grading-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .grading-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
            text-align: center;
        }
        .grading-table th {
            background-color: #333333;
            color: #ffffff;
            padding: 2px 3px;
            border: 0.5px solid #000000;
        }
        .grading-table td {
            padding: 2px 3px;
            border: 0.5px solid #999999;
        }
    </style>
</head>
<body>

@foreach($variants as $vIndex => $variant)
<div class="exam-sheet">
    <!-- 1. ШАПКА: Логотип EDUSFERA, название платформы, слоган, QR-код -->
    <table class="brand-header-table">
        <tr>
            <td class="brand-col" style="width: 70%;">
                <div class="logo-box">
                    <svg width="26" height="26" viewBox="0 0 64 64" style="vertical-align: middle;">
                        <rect width="64" height="64" rx="14" fill="#000000"/>
                        <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#ffffff" stroke-width="6" stroke-linejoin="round"/>
                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#ffffff"/>
                    </svg>
                </div>
                <div class="brand-name">EDUSFERA</div>
                <span class="brand-divider">|</span>
                <span class="brand-slogan">Не зубри. Знай.</span>
                <div class="brand-desc">Платформа для репетиторов</div>
            </td>
            <td class="brand-col" style="width: 30%; text-align: right;">
                <div class="qr-card">
                    <table style="border-collapse: collapse; margin: 0; padding: 0;">
                        <tr>
                            <td class="qr-text">
                                Больше тестов<br>и заданий<br>на <b>edusfera.by</b>
                            </td>
                            <td style="vertical-align: middle;">
                                {!! \App\Services\Exam\ExamDocumentParser::renderQrTableHtml(42) !!}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. ПАСПОРТ ТЕСТА: Название предмета, тип работы, время, баллы -->
    <table class="passport-table">
        <tr>
            <td style="vertical-align: bottom;">
                <div class="passport-title">{{ $order->subject ?? 'Математика' }}</div>
                <div class="passport-subtitle">{{ $testTitle ?? 'Пробный тест в формате ЦТ / ЦЭ 2026' }}</div>
            </td>
            <td style="vertical-align: bottom; width: 230px;">
                <div class="passport-badge-box">
                    <table class="passport-meta-table">
                        <tr>
                            <td style="padding-right: 6px; width: 18px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                                    <rect x="4" y="2" width="16" height="20" rx="2"/>
                                    <line x1="8" y1="6" x2="16" y2="6"/>
                                    <line x1="16" y1="14" x2="16" y2="18"/>
                                    <circle cx="8" cy="10" r="1" fill="#000"/>
                                    <circle cx="12" cy="10" r="1" fill="#000"/>
                                    <circle cx="16" cy="10" r="1" fill="#000"/>
                                    <circle cx="8" cy="14" r="1" fill="#000"/>
                                    <circle cx="12" cy="14" r="1" fill="#000"/>
                                    <circle cx="8" cy="18" r="1" fill="#000"/>
                                    <circle cx="12" cy="18" r="1" fill="#000"/>
                                </svg>
                            </td>
                            <td>
                                <div>Время выполнения: <b>{{ $timeMinutes ?? 90 }} минут</b></div>
                                <div>Количество заданий: <b>{{ (count($variant['part_a'] ?? []) + count($variant['part_b'] ?? [])) }}</b></div>
                                <div>Максимальный балл: <b>{{ $maxScore ?? 100 }}</b></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="black-rule"></div>

    <!-- 3. ИНСТРУКЦИЯ К ТЕСТУ -->
    <div class="instruction-box">
        <span class="info-circle">i</span>
        <b>Инструкция</b><br>
        {{ $instructionText ?? 'В тесте представлены задания с выбором одного правильного ответа (Часть А) и с кратким числовым ответом (Часть Б). В бланке ответов внизу листа запишите номер задания и соответствующий ответ.' }}
    </div>

    <!-- 4. ОСНОВНАЯ ЧАСТЬ: ЧАСТЬ А -->
    @if(!empty($variant['part_a']))
    <table class="section-bar">
        <tr>
            <td class="section-title">Часть А. Задания с выбором одного правильного ответа</td>
            <td style="text-align: right;">
                <span class="section-pill">Задания А1–А{{ count($variant['part_a']) }}</span>
            </td>
        </tr>
    </table>

    @foreach($variant['part_a'] as $task)
    <div class="task-item">
        <table class="task-table">
            <tr>
                <td class="task-num-col">
                    <div class="task-badge">А{{ $task['num'] }}</div>
                </td>
                <td class="task-content-col">
                    <div class="task-condition">{!! $task['condition_html'] !!}</div>

                    @if(!empty($task['options']))
                    <table class="options-table">
                        <tr>
                            @foreach($task['options'] as $opt)
                            <td>
                                <span class="opt-box">{{ $opt['letter'] }}</span>
                                <span class="opt-value">{!! $opt['text'] !!}</span>
                            </td>
                            @endforeach
                        </tr>
                    </table>
                    @endif
                </td>
                @if(!empty($task['diagram_b64']))
                <td class="task-diagram-col">
                    <img src="data:image/svg+xml;base64,{{ $task['diagram_b64'] }}" style="display: block; max-width: 120px; max-height: 95px; margin-left: auto;">
                </td>
                @elseif(!empty($task['diagram_svg']))
                <td class="task-diagram-col">
                    <img src="data:image/svg+xml;base64,{{ base64_encode($task['diagram_svg']) }}" style="display: block; max-width: 120px; max-height: 95px; margin-left: auto;">
                </td>
                @endif
            </tr>
        </table>
    </div>
    @endforeach
    @endif

    <!-- ОСНОВНАЯ ЧАСТЬ: ЧАСТЬ Б -->
    @if(!empty($variant['part_b']))
    <table class="section-bar" style="margin-top: 10px;">
        <tr>
            <td class="section-title">Часть Б. Задания с кратким числовым ответом</td>
            <td style="text-align: right;">
                <span class="section-pill">Задания Б1–Б{{ count($variant['part_b']) }}</span>
            </td>
        </tr>
    </table>

    @foreach($variant['part_b'] as $task)
    <div class="task-item">
        <table class="task-table">
            <tr>
                <td class="task-num-col">
                    <div class="task-badge">Б{{ $task['num'] }}</div>
                </td>
                <td class="task-content-col">
                    <div class="task-condition">{!! $task['condition_html'] !!}</div>
                    <table class="ans-cells-table">
                        <tr>
                            <td class="ans-label">Ответ:</td>
                            <td>
                                <span class="exam-cell sign-cell">±</span>
                                <span class="exam-cell">&nbsp;</span>
                                <span class="exam-cell">&nbsp;</span>
                                <span class="exam-cell">&nbsp;</span>
                                <span class="exam-cell">&nbsp;</span>
                            </td>
                            <td class="ans-hint">(в бланк ответов запишите полученное целое число)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    @endforeach
    @endif

    <!-- 5. БЛАНК ОТВЕТОВ (ВНИЗУ ЛИСТА ВАРИАНТА) -->
    <div class="answer-grid-container">
        <table class="grid-wrapper-table">
            <tr>
                <td class="grid-label-col">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="padding-right: 5px; vertical-align: middle;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                            </td>
                            <td style="vertical-align: middle;">
                                <div style="font-size: 8pt; font-weight: bold; text-transform: uppercase;">Бланк ответов</div>
                                <div style="font-size: 7pt; color: #52525b; font-weight: bold;">Вариант № {{ $variant['number'] }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="vertical-align: middle;">
                    <table class="grid-table">
                        <tr>
                            <th style="width: 55px; font-size: 7pt;">№ задания</th>
                            @foreach($variant['part_a'] ?? [] as $t)
                            <th style="width: 24px;">А{{ $t['num'] }}</th>
                            @endforeach
                            @foreach($variant['part_b'] ?? [] as $t)
                            <th style="width: 24px;">Б{{ $t['num'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <td style="font-size: 7.5pt; font-weight: bold; background: #f4f4f5;">Ответ</td>
                            @foreach($variant['part_a'] ?? [] as $t)
                            <td>&nbsp;</td>
                            @endforeach
                            @foreach($variant['part_b'] ?? [] as $t)
                            <td>&nbsp;</td>
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
@endforeach

<!-- 6. СВОДНАЯ СТРАНИЦА ПРЕПОДАВАТЕЛЯ С КЛЮЧАМИ И КРИТЕРИЯМИ -->
@if(!empty($masterTable))
<div class="teacher-sheet">
    <div class="teacher-header-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="teacher-badge">КОНФИДЕНЦИАЛЬНО • МАТЕРИАЛЫ ДЛЯ УЧИТЕЛЯ</div>
                    <div class="teacher-title">Ключи правильных ответов и критерии оценивания</div>
                    <div class="teacher-sub">{{ $order->subject ?? 'Математика' }} — {{ $order->topic ?? 'Проверочная работа' }} • {{ $testTitle ?? 'ЦТ / ЦЭ 2026' }}</div>
                </td>
                <td style="text-align: right; vertical-align: middle; width: 140px;">
                    <div style="font-size: 7.5pt; font-weight: bold; color: #333;">EDUSFERA EVALUATION</div>
                    <div style="font-size: 6.5pt; color: #666;">Версия стандарта 2026.1</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="master-table">
        <thead>
            <tr>
                <th style="width: 65px; text-align: center;">Вариант</th>
                <th style="width: 38%;">Ответы Части А</th>
                @if(!empty($variant['part_b']))
                <th style="width: 22%;">Ответы Части Б</th>
                @endif
                <th>Методические пояснения и типичные ловушки</th>
            </tr>
        </thead>
        <tbody>
            @foreach($masterTable as $row)
            <tr>
                <td style="text-align: center; font-weight: bold;">Вариант {{ $row['variant'] }}</td>
                <td>{{ $row['part_a'] }}</td>
                @if(!empty($variant['part_b']))
                <td style="font-weight: bold; font-family: monospace;">{{ $row['part_b'] }}</td>
                @endif
                <td>{{ $row['trap'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="grading-box">
        <div class="grading-title">Шкала перевода баллов в 10-балльную отметку (стандарт Беларуси)</div>
        <table class="grading-table">
            <thead>
                <tr>
                    <th>Отметка (балл)</th>
                    <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                    <th>6</th><th>7</th><th>8</th><th>9</th><th>10</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: bold; background: #f3f4f6;">Первичный %</td>
                    <td>1–15%</td><td>16–25%</td><td>26–35%</td><td>36–49%</td><td>50–64%</td>
                    <td>65–74%</td><td>75–84%</td><td>85–92%</td><td>93–97%</td><td>98–100%</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background: #f3f4f6;">Уровень</td>
                    <td colspan="2">Низкий</td>
                    <td colspan="2">Удовлетворительный</td>
                    <td colspan="2">Средний</td>
                    <td colspan="2">Достаточный</td>
                    <td colspan="2">Высокий</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

</body>
</html>
