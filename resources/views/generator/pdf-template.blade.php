<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>{{ $order->subject }} — {{ $order->topic }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.55;
            color: #111827;
        }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .badge {
            background-color: #111827;
            color: #ffffff;
            font-size: 8.5pt;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 6px;
        }
        .title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
        }
        .meta {
            font-size: 9.5pt;
            color: #4b5563;
            margin-top: 4px;
        }
        .order-badge {
            float: right;
            font-size: 8pt;
            color: #6b7280;
            font-family: monospace;
        }
        .content {
            margin-top: 15px;
        }
        h1, h2, h3, h4 {
            color: #0f172a;
            page-break-after: avoid;
        }
        h2 {
            font-size: 13pt;
            border-bottom: 1.5px solid #111827;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        h3 {
            font-size: 11.5pt;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-top: 18px;
            margin-bottom: 8px;
            color: #1e293b;
        }
        h4 {
            font-size: 10.5pt;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        p {
            margin-top: 4px;
            margin-bottom: 8px;
        }
        ul, ol {
            margin-top: 4px;
            margin-bottom: 8px;
            padding-left: 24px;
        }
        li {
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        hr {
            border: 0;
            border-top: 1px dashed #cbd5e1;
            margin: 18px 0;
        }
        .footer {
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            margin-top: 30px;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="order-badge">Заказ: # {{ $order->order_number }}</div>
        <span class="badge">БЕЛАРУСЬ • ЦТ/ЦЭ 2026 • РЭСПУБЛІКАНСКІ СТАНДАРТ</span>
        <div class="title">{{ $order->subject }}: {{ $order->topic }}</div>
        <div class="meta">
            Комплект из {{ $order->variant_count }} вариантов самостоятельной работы с единой мастер-таблицей правильных ответов для преподавателя.
        </div>
    </div>

    <div class="content">
        @if(!empty($htmlContent))
            {!! $htmlContent !!}
        @else
            {!! nl2br(e($content)) !!}
        @endif
    </div>

    <div class="footer">
        Сгенерировано образовательной платформой Edusfera.by (ИИ-Методист ЦТ/ЦЭ Республики Беларусь). Копия документа для {{ $order->email }}.
    </div>
</body>
</html>
