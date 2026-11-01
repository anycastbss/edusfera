<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание на неделю ({{ $weekStart->translatedFormat('j M') }} — {{ $weekEnd->translatedFormat('j M Y') }}) — Edusfera</title>
    <link rel="icon" type="image/png" href="/favicon.png">

    <style>
        /* ─── BASE RESET & DESK PREVIEW ─── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── TOP CONTROL BAR (SCREEN ONLY) ─── */
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        .print-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .print-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #7D39EB, #9061F9);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .print-week-title {
            font-size: 15px;
            font-weight: 700;
            color: #f8fafc;
        }

        .print-toolbar-center {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #f1f5f9;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-nav.active {
            background: rgba(125, 57, 235, 0.3);
            border-color: #7D39EB;
            color: #d8b4fe;
        }

        .print-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 10px;
            background: #10b981;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.35);
            transition: all 0.15s ease;
        }

        .btn-print:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.45);
        }

        .btn-close {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        /* ─── A4 PAPER SHEET CONTAINER ─── */
        .paper-wrapper {
            padding: 30px 16px 60px;
            display: flex;
            justify-content: center;
        }

        .a4-sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 15mm 12mm 15mm;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            position: relative;
            color: #0f172a;
        }

        /* ─── PAPER HEADER ─── */
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 14px;
        }

        .sheet-brand {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sheet-brand-star {
            color: #7c3aed;
        }

        .sheet-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #334155;
            margin-top: 2px;
        }

        .sheet-period {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 3px;
        }

        .sheet-meta {
            text-align: right;
            font-size: 11px;
            color: #475569;
            line-height: 1.45;
        }

        .sheet-meta strong {
            color: #0f172a;
            font-size: 12px;
        }

        .sheet-stats-pills {
            display: flex;
            gap: 6px;
            margin-top: 6px;
            justify-content: flex-end;
        }

        .stat-pill {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #1e293b;
        }

        /* ─── DAYS GRID & LESSONS ─── */
        .days-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .day-block {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            page-break-inside: avoid;
            background: #ffffff;
        }

        .day-block.is-today {
            border-color: #0284c7;
        }

        .day-block.is-weekend {
            border-style: dashed;
            background: #fafafa;
        }

        .day-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 10px;
            font-size: 12px;
        }

        .day-block.is-today .day-header {
            background: #f0f9ff;
            border-bottom-color: #bae6fd;
        }

        .day-title-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .day-name {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
        }

        .day-block.is-today .day-name {
            color: #0369a1;
        }

        .day-date {
            font-weight: 600;
            color: #475569;
        }

        .today-badge {
            background: #0284c7;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .day-count-badge {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
        }

        /* ─── LESSONS TABLE ─── */
        .lessons-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .lessons-table th {
            text-align: left;
            padding: 4px 8px;
            background: #ffffff;
            font-weight: 700;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .lessons-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #1e293b;
        }

        .lessons-table tr:last-child td {
            border-bottom: none;
        }

        .lessons-table tr:nth-child(even) td {
            background: #fbfcfe;
        }

        .col-check {
            width: 24px;
            text-align: center;
        }

        .print-checkbox {
            width: 14px;
            height: 14px;
            border: 1.5px solid #475569;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
        }

        .col-time {
            width: 90px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 800;
            font-size: 11.5px;
            color: #0f172a;
            white-space: nowrap;
        }

        .col-time small {
            display: block;
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
        }

        .col-student {
            width: 180px;
            font-weight: 700;
            color: #0f172a;
        }

        .student-contact {
            font-size: 9.5px;
            font-weight: 500;
            color: #64748b;
        }

        .col-subject {
            font-weight: 600;
            color: #334155;
        }

        .col-status {
            width: 110px;
            text-align: right;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        .status-badge.status-paid {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }

        .status-badge.status-completed {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }

        .status-badge.status-pending {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .empty-day-row {
            padding: 7px 10px;
            font-size: 10.5px;
            color: #94a3b8;
            font-style: italic;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .empty-day-ruled {
            flex: 1;
            height: 1px;
            border-bottom: 1px dashed #cbd5e1;
            margin-left: 14px;
        }

        /* ─── PAPER FOOTER: NOTES & HANDWRITING ─── */
        .sheet-footer-notes {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            background: #ffffff;
            page-break-inside: avoid;
        }

        .notes-heading {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notes-lines {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .note-line {
            height: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sheet-bottom-bar {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9.5px;
            color: #64748b;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }

        .sheet-bottom-bar a {
            color: #64748b;
            text-decoration: none;
        }

        /* ─── PRINT MEDIA SPECIFIC RULES ─── */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .paper-wrapper {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .a4-sheet {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm 10mm 8mm 10mm;
            }

            .day-block {
                border-color: #94a3b8 !important;
            }

            .day-header {
                background: #f1f5f9 !important;
                border-bottom-color: #94a3b8 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .today-badge {
                background: #0284c7 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .status-badge {
                border-color: #64748b !important;
            }

            .status-badge.status-paid {
                background: #e2e8f0 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    {{-- ПАНЕЛЬ УПРАВЛЕНИЯ (СКРЫВАЕТСЯ ПРИ ПЕЧАТИ) --}}
    <div class="print-toolbar">
        <div class="print-toolbar-left">
            <span class="print-brand-badge">🖨️ Бланк А4</span>
            <span class="print-week-title">График на неделю: {{ $weekStart->translatedFormat('j F') }} — {{ $weekEnd->translatedFormat('j F Y') }}</span>
        </div>

        <div class="print-toolbar-center">
            <a href="?week={{ $prevWeek }}" class="btn-nav" title="Предыдущая неделя">
                &larr; Пред. неделя
            </a>
            <a href="?week={{ $currentWeek }}" class="btn-nav {{ $weekStart->format('Y-m-d') === $currentWeek ? 'active' : '' }}" title="Текущая неделя">
                Текущая неделя
            </a>
            <a href="?week={{ $nextWeek }}" class="btn-nav" title="Следующая неделя">
                След. неделя &rarr;
            </a>
        </div>

        <div class="print-toolbar-right">
            <button type="button" class="btn-print" onclick="window.print();" title="Отправить на принтер (Ctrl + P)">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                </svg>
                <span>Распечатать (А4)</span>
            </button>

            <a href="/admin/lessons" class="btn-close" title="Вернуться в панель управления">
                <span>✕ Закрыть</span>
            </a>
        </div>
    </div>

    {{-- ЛИСТ А4 --}}
    <div class="paper-wrapper">
        <div class="a4-sheet">

            {{-- ШАПКА БЛАНКА --}}
            <div class="sheet-header">
                <div>
                    <div class="sheet-brand">
                        <span>Edusfera<span class="sheet-brand-star">.by</span></span>
                    </div>
                    <div class="sheet-title">График учебных занятий на неделю</div>
                    <div class="sheet-period">{{ $weekStart->translatedFormat('j F') }} — {{ $weekEnd->translatedFormat('j F Y') }} г.</div>
                </div>

                <div class="sheet-meta">
                    <div>Преподаватель: <strong>{{ $user->name }}</strong></div>
                    <div>Сформировано: {{ now($displayTz)->format('d.m.Y H:i') }} (Минск)</div>
                    <div class="sheet-stats-pills">
                        <span class="stat-pill">Уроков: {{ $totalLessons }}</span>
                        <span class="stat-pill">Часов: {{ $totalHours }} ч</span>
                        <span class="stat-pill">Учеников: {{ $totalStudentsCount }}</span>
                    </div>
                </div>
            </div>

            {{-- 7 ДНЕЙ НЕДЕЛИ --}}
            <div class="days-container">
                @foreach ($days as $day)
                    <div class="day-block {{ $day['is_today'] ? 'is-today' : '' }} {{ $day['is_weekend'] ? 'is-weekend' : '' }}">
                        <div class="day-header">
                            <div class="day-title-left">
                                <span class="day-name">{{ $day['day_name'] }}</span>
                                <span class="day-date">{{ $day['date_formatted'] }}</span>
                                @if ($day['is_today'])
                                    <span class="today-badge">Сегодня</span>
                                @endif
                            </div>
                            <span class="day-count-badge">
                                @if (count($day['lessons']) > 0)
                                    Занятий: {{ count($day['lessons']) }}
                                @else
                                    Свободный день
                                @endif
                            </span>
                        </div>

                        @if (count($day['lessons']) > 0)
                            <table class="lessons-table">
                                <thead>
                                    <tr>
                                        <th class="col-check">✓</th>
                                        <th class="col-time">Время</th>
                                        <th class="col-student">Ученик</th>
                                        <th class="col-subject">Предмет / Тема занятия</th>
                                        <th class="col-status">Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($day['lessons'] as $lesson)
                                        @php
                                            $start = $lesson->start_time->setTimezone($displayTz);
                                            $end = $lesson->end_time->setTimezone($displayTz);
                                            $student = $lesson->student ?? $lesson->parent;
                                            $statusText = match ($lesson->status) {
                                                \App\Models\Lesson::STATUS_CONFIRMED => ($lesson->payment_status === \App\Models\Lesson::PAYMENT_PAID ? 'Оплачен' : 'Подтверждён'),
                                                \App\Models\Lesson::STATUS_COMPLETED => 'Завершён',
                                                \App\Models\Lesson::STATUS_PENDING => 'Ожидает',
                                                default => $lesson->status,
                                            };
                                            $statusClass = match (true) {
                                                $lesson->payment_status === \App\Models\Lesson::PAYMENT_PAID => 'status-paid',
                                                $lesson->status === \App\Models\Lesson::STATUS_COMPLETED => 'status-completed',
                                                $lesson->status === \App\Models\Lesson::STATUS_PENDING => 'status-pending',
                                                default => '',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="col-check">
                                                <span class="print-checkbox"></span>
                                            </td>
                                            <td class="col-time">
                                                {{ $start->format('H:i') }} – {{ $end->format('H:i') }}
                                                <small>({{ $lesson->duration_minutes }} мин)</small>
                                            </td>
                                            <td class="col-student">
                                                {{ $student?->name ?? 'Ученик' }}
                                                @if ($student?->phone)
                                                    <div class="student-contact">{{ $student->phone }}</div>
                                                @endif
                                            </td>
                                            <td class="col-subject">
                                                <strong>{{ $lesson->subject ?? ($lesson->tutor?->tutorProfile?->subject ?? ($user->tutorProfile?->subject ?? 'Индивидуальное занятие')) }}</strong>
                                                @if (! empty($lesson->notes))
                                                    <div style="font-size: 9.5px; color: #475569; margin-top: 1px;">
                                                        {{ \Illuminate\Support\Str::limit($lesson->notes, 75) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="col-status">
                                                <span class="status-badge {{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty-day-row">
                                <span>— Нет запланированных занятий (окна для записи / отработок) —</span>
                                <div class="empty-day-ruled"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- ПОЛЕ ДЛЯ РУЧНЫХ ЗАМЕТОК И ПАМЯТКИ --}}
            <div class="sheet-footer-notes">
                <div class="notes-heading">
                    <span>📝 Заметки и важные напоминания на неделю (для ручных записей):</span>
                    <span style="font-weight: 500; font-size: 9px; color: #94a3b8;">Повесьте лист на доску или вложите в ежедневник</span>
                </div>
                <div class="notes-lines">
                    <div class="note-line"></div>
                    <div class="note-line"></div>
                    <div class="note-line"></div>
                </div>
            </div>

            {{-- ПОДВАЛ СТРАНИЦЫ --}}
            <div class="sheet-bottom-bar">
                <span>Платформа онлайн-репетиторов Edusfera.by · Официальный график занятий</span>
                <span>Личный кабинет: <a href="https://edusfera.by/admin/lessons">edusfera.by/admin/lessons</a></span>
            </div>

        </div>
    </div>

    <script>
        // Быстрая печать по горячим клавишам Ctrl+P / Cmd+P
        window.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
                window.print();
            }
        });
    </script>
</body>
</html>
