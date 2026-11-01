<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Официальное свидетельство эксперта — {{ $tutorFullName }} — Edusfera</title>
    <link rel="icon" type="image/png" href="/favicon.png">

    <!-- Google Fonts for Academic Certificates -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ─── BASE RESET ─── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cormorant Garamond', 'Times New Roman', serif;
            background-color: #0F172A;
            color: #1E293B;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* ─── TOP TOOLBAR (SCREEN ONLY) ─── */
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
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
            padding: 4px 12px;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .print-toolbar-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #E2E8F0;
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
            background: linear-gradient(135deg, #10B981, #059669);
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 800;
            padding: 9px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            transition: all 0.15s ease;
        }
        .btn-print:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
        }

        .btn-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: #E2E8F0;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
        }

        /* ─── DESK CANVAS WRAPPER (SCREEN ONLY) ─── */
        .sheet-container {
            padding: 30px 16px 60px;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* ─── А4 CERTIFICATE SHEET ─── */
        .tutor-certificate-sheet {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            background: #FFFDF9;
            color: #1E293B;
            padding: 10mm;
            box-sizing: border-box;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Внешняя золотая защитная рамка */
        .tutor-cert-outer-frame {
            position: relative;
            border: 2px solid #C59B27;
            padding: 6px;
            background: linear-gradient(135deg, #FFFDF9 0%, #FAF6EE 50%, #FFFDF9 100%);
            box-shadow: inset 0 0 0 1px #8C6D1F, 0 10px 30px -5px rgba(140, 109, 31, 0.15);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Внутренняя гильоширная рамка */
        .tutor-cert-inner-frame {
            position: relative;
            border: 1px solid rgba(197, 155, 39, 0.5);
            padding: 24px 30px 20px;
            text-align: center;
            background: #FFFFFF;
            box-shadow: inset 0 0 0 3px rgba(255, 253, 249, 0.9), inset 0 0 0 4px rgba(197, 155, 39, 0.25);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Угловые виньетки */
        .tutor-cert-corner {
            position: absolute;
            width: 44px;
            height: 44px;
            pointer-events: none;
            z-index: 2;
        }
        .tutor-cert-corner--tl { top: 6px; left: 6px; }
        .tutor-cert-corner--tr { top: 6px; right: 6px; transform: scaleX(-1); }
        .tutor-cert-corner--bl { bottom: 6px; left: 6px; transform: scaleY(-1); }
        .tutor-cert-corner--br { bottom: 6px; right: 6px; transform: scale(-1); }

        /* Водяной знак */
        .tutor-cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            height: 320px;
            opacity: 0.04;
            pointer-events: none;
            z-index: 1;
        }

        /* Шапка */
        .tutor-cert-header {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }
        .tutor-cert-state-line {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            color: #78350F;
        }
        .tutor-cert-committee-line {
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #92400E;
            margin-top: 1px;
        }
        .tutor-cert-divider-ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            max-width: 440px;
            margin: 6px auto 10px;
        }
        .tutor-cert-divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #C59B27, transparent);
        }
        .tutor-cert-divider-star {
            color: #C59B27;
            font-size: 11px;
        }

        .tutor-cert-main-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #0F172A;
            margin: 0;
            line-height: 1.1;
        }
        .tutor-cert-main-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #B45309;
            margin-top: 4px;
        }

        .tutor-cert-meta-strip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: 10px auto 14px;
            padding: 4px 18px;
            border-radius: 9999px;
            background: rgba(197, 155, 39, 0.08);
            border: 1px solid rgba(197, 155, 39, 0.25);
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }
        .tutor-cert-meta-item b {
            color: #0F172A;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        /* Тело */
        .tutor-cert-content-body {
            position: relative;
            z-index: 2;
            max-width: 660px;
            margin: 0 auto;
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
            text-align: center;
        }
        .tutor-cert-intro-phrase {
            font-size: 15px;
            color: #475569;
            font-style: italic;
        }
        .tutor-cert-recipient-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            color: #0F172A;
            margin: 6px 0 4px;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }
        .tutor-cert-recipient-underline {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 auto 10px;
            max-width: 260px;
        }
        .tutor-cert-recipient-line {
            flex: 1;
            height: 1px;
            background: #C59B27;
        }
        .tutor-cert-recipient-diamond {
            width: 5px;
            height: 5px;
            background: #C59B27;
            transform: rotate(45deg);
        }

        .tutor-cert-status-callout {
            margin: 10px auto 12px;
            padding: 8px 22px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.5) 0%, rgba(253, 230, 138, 0.3) 100%);
            border-top: 1px solid rgba(217, 119, 6, 0.35);
            border-bottom: 1px solid rgba(217, 119, 6, 0.35);
            display: inline-block;
        }
        .tutor-cert-status-callout-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 13.5px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #92400E;
        }

        .tutor-cert-legal-desc {
            font-size: 13px;
            line-height: 1.55;
            color: #475569;
            margin: 0 auto 10px;
            text-align: justify;
            text-align-last: center;
        }
        .tutor-cert-portfolio-notice {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #1E3A8A;
            background: rgba(239, 246, 255, 0.85);
            border: 1px dashed rgba(59, 130, 246, 0.35);
            padding: 5px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Подвал */
        .tutor-cert-credentials-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
            padding: 14px 10px 4px;
            border-top: 1px solid rgba(197, 155, 39, 0.25);
        }

        .tutor-cert-sign-col { text-align: left; }
        .tutor-cert-sign-role {
            font-family: 'Montserrat', sans-serif;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748B;
        }
        .tutor-cert-facsimile-box {
            position: relative;
            height: 44px;
            display: flex;
            align-items: center;
        }
        .tutor-cert-sign-printed-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 17px;
            font-weight: 700;
            color: #0F172A;
            letter-spacing: 0.03em;
            border-top: 1px solid #CBD5E1;
            padding-top: 2px;
            display: inline-block;
            min-width: 160px;
        }

        .tutor-cert-seal-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .tutor-cert-gold-medal {
            position: relative;
            width: 98px;
            height: 98px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #FFF6CC 0%, #E6C25B 45%, #C59B27 75%, #8C6D1F 100%);
            box-shadow: 0 8px 20px -4px rgba(140, 109, 31, 0.45), inset 0 2px 4px rgba(255, 255, 255, 0.9), inset 0 -2px 4px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #8C6D1F;
            z-index: 2;
        }
        .tutor-cert-gold-medal-inner {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            border: 1px dashed rgba(120, 53, 15, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3px;
            box-sizing: border-box;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6);
        }
        .tutor-cert-medal-star { font-size: 13px; color: #78350F; line-height: 1; }
        .tutor-cert-medal-txt-top {
            font-family: 'Montserrat', sans-serif;
            font-size: 6.5px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #78350F;
        }
        .tutor-cert-medal-txt-main {
            font-family: 'Cinzel', serif;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.1em;
            color: #451A03;
            margin: 1px 0;
        }
        .tutor-cert-medal-txt-sub {
            font-family: 'Montserrat', sans-serif;
            font-size: 6px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #78350F;
        }

        .tutor-cert-ribbons {
            position: absolute;
            bottom: -16px;
            display: flex;
            gap: 5px;
            z-index: 1;
        }
        .tutor-cert-ribbon-tail {
            width: 16px;
            height: 28px;
            background: linear-gradient(180deg, #DC2626 0%, #991B1B 100%);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }
        .tutor-cert-ribbon-tail--green {
            background: linear-gradient(180deg, #16A34A 0%, #166534 100%);
        }

        .tutor-cert-verify-col {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-end;
            padding-bottom: 2px;
        }
        .tutor-cert-issue-info {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            line-height: 1.5;
        }

        .tutor-cert-bottom-security-bar {
            position: relative;
            z-index: 2;
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid rgba(197, 155, 39, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Montserrat', sans-serif;
            font-size: 7.5px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #94A3B8;
        }

        /* ─── PRINT MEDIA QUERY (STRICTLY 1 A4 PAGE) ─── */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            body, html {
                margin: 0 !important;
                padding: 0 !important;
                background: #FFFFFF !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print, .print-toolbar {
                display: none !important;
            }

            .sheet-container {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            .tutor-certificate-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                padding: 8mm !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                page-break-inside: avoid !important;
                background: #FFFFFF !important;
            }

            .tutor-cert-outer-frame {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Панель управления печатью (только на экране) -->
    <div class="print-toolbar no-print">
        <div class="print-toolbar-left">
            <div class="print-brand-badge">
                <span>Edusfera.by</span>
            </div>
            <span class="print-toolbar-title">Печать официального свидетельства эксперта (А4)</span>
        </div>
        <div class="print-toolbar-right">
            <button type="button" onclick="window.print()" class="btn-print">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                </svg>
                <span>Распечатать на принтере (А4)</span>
            </button>
            <button type="button" onclick="window.close()" class="btn-close">
                <span>Закрыть</span>
            </button>
        </div>
    </div>

    <!-- Контейнер листа А4 -->
    <div class="sheet-container">
        <div class="tutor-certificate-sheet">
            <div class="tutor-cert-outer-frame">
                {{-- Угловые виньетки --}}
                <svg class="tutor-cert-corner tutor-cert-corner--tl" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                    <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                    <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                    <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <svg class="tutor-cert-corner tutor-cert-corner--tr" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                    <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                    <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                    <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <svg class="tutor-cert-corner tutor-cert-corner--bl" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                    <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                    <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                    <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <svg class="tutor-cert-corner tutor-cert-corner--br" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2H22C22 13.0457 13.0457 22 2 22V2Z" stroke="#C59B27" stroke-width="1.5" fill="none"/>
                    <path d="M4 4H18C18 11.732 11.732 18 4 18V4Z" stroke="#8C6D1F" stroke-width="0.75" fill="rgba(197, 155, 39, 0.08)"/>
                    <circle cx="10" cy="10" r="2.5" fill="#C59B27"/>
                    <path d="M2 30V42H14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M30 2H42V14" stroke="#C59B27" stroke-width="1.5" stroke-linecap="round"/>
                </svg>

                <div class="tutor-cert-inner-frame">
                    {{-- Защитный водяной знак герба --}}
                    <svg class="tutor-cert-watermark" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="94" stroke="#8C6D1F" stroke-width="2" stroke-dasharray="4 4"/>
                        <circle cx="100" cy="100" r="82" stroke="#8C6D1F" stroke-width="1"/>
                        <polygon points="100,28 118,72 166,74 128,104 142,150 100,124 58,150 72,104 34,74 82,72" fill="#C59B27"/>
                    </svg>

                    {{-- Верхняя гербовая часть --}}
                    <div class="tutor-cert-header">
                        <div class="tutor-cert-crest-wrap">
                            <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 44C11 36 12 24 20 16C23 12 28 8 32 6C36 8 41 12 44 16C52 24 53 36 50 44C46 51 39 55 32 56C25 55 18 51 14 44Z" stroke="#C59B27" stroke-width="2" fill="rgba(254, 243, 199, 0.4)"/>
                                <path d="M20 50C24 52 28 53 32 53C36 53 40 52 44 50V54C40 56 36 57 32 57C28 57 24 56 20 54V50Z" fill="#DC2626"/>
                                <path d="M20 54C24 56 28 57 32 57C36 57 40 56 44 54V56C40 58 36 59 32 59C28 59 24 58 20 56V54Z" fill="#16A34A"/>
                                <path d="M24 38C28 36 32 37 32 37C32 37 36 36 40 38V27C36 25 32 26 32 26C32 26 28 25 24 27V38Z" fill="#C59B27"/>
                                <polygon points="32,15 34,19 38,19 35,21 36,25 32,23 28,25 29,21 26,19 30,19" fill="#B45309"/>
                            </svg>
                        </div>
                        <div class="tutor-cert-state-line">РЕСПУБЛИКА БЕЛАРУСЬ</div>
                        <div class="tutor-cert-committee-line">ОБЩЕСТВЕННЫЙ МЕТОДИЧЕСКИЙ СОВЕТ EDUSFERA</div>

                        <div class="tutor-cert-divider-ornament">
                            <div class="tutor-cert-divider-line"></div>
                            <div class="tutor-cert-divider-star">★ &nbsp; ★ &nbsp; ★</div>
                            <div class="tutor-cert-divider-line"></div>
                        </div>

                        <h1 class="tutor-cert-main-title">СВИДЕТЕЛЬСТВО</h1>
                        <div class="tutor-cert-main-subtitle">О ПРИСВОЕНИИ СТАТУСА ЭКСПЕРТА МЕТОДИЧЕСКОГО СОВЕТА</div>

                        <div class="tutor-cert-meta-strip">
                            <span class="tutor-cert-meta-item">СЕРИЯ: <b>ЭС-2026</b></span>
                            <span style="color:#C59B27;">•</span>
                            <span class="tutor-cert-meta-item">РЕГ. НОМЕР: <b>{{ $councilCertNumber }}</b></span>
                            <span style="color:#C59B27;">•</span>
                            <span class="tutor-cert-meta-item">РЕЕСТР: <b>АКТИВЕН (БЕССРОЧНО)</b></span>
                        </div>
                    </div>

                    {{-- Содержание свидетельства --}}
                    <div class="tutor-cert-content-body">
                        <div class="tutor-cert-intro-phrase">
                            Настоящим решением Экспертно-методической коллегии удостоверяется, что
                        </div>

                        <div class="tutor-cert-recipient-name">
                            {{ $tutorFullName }}
                        </div>

                        <div class="tutor-cert-recipient-underline">
                            <div class="tutor-cert-recipient-line"></div>
                            <div class="tutor-cert-recipient-diamond"></div>
                            <div class="tutor-cert-recipient-line"></div>
                        </div>

                        <div style="font-size: 14px; color: #334155;">
                            аккредитован(а) в качестве ведущего наставника и включен(а) в состав <strong>Общественного методического совета платформы Edusfera</strong> с наделением постоянным статусом:
                        </div>

                        <div class="tutor-cert-status-callout">
                            <div class="tutor-cert-status-callout-text">
                                «ВЕДУЩИЙ ЭКСПЕРТ ВЫСШЕЙ ПЕДАГОГИЧЕСКОЙ КВАЛИФИКАЦИИ»
                            </div>
                        </div>

                        <div class="tutor-cert-legal-desc">
                            Свидетельство удостоверяет высокий уровень профессионального педагогического мастерства, экспертное участие в апробации отечественного цифрового класса, стандартов конфиденциальности «Приватный кабинет», эргономического режима «Комфортное зрение 50+» и методическое руководство качеством дистанционного образования в Республике Беларусь.
                        </div>


                    </div>

                    {{-- Блок подписи, гербовой печати и верификации --}}
                    <div class="tutor-cert-credentials-grid">
                        {{-- Левая колонка: Подпись председателя оргкомитета --}}
                        <div class="tutor-cert-sign-col">
                            <div class="tutor-cert-sign-role">Председатель оргкомитета Edusfera:</div>
                            <div class="tutor-cert-facsimile-box">
                                <svg width="150" height="42" viewBox="0 0 180 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 38C22 18 36 12 44 24C50 32 45 44 38 42C30 40 28 28 38 18C48 8 62 14 70 28C74 35 80 40 88 38C96 36 102 26 108 22C114 18 126 28 135 24C142 21 148 16 162 14" stroke="#1D4ED8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M28 26C45 22 75 25 110 20C130 17 155 18 172 15" stroke="#1D4ED8" stroke-width="1.4" stroke-linecap="round"/>
                                    <path d="M72 16L78 34M84 15L90 32" stroke="#1D4ED8" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="tutor-cert-sign-printed-name">
                                С.С. Башмаков
                            </div>
                        </div>

                        {{-- Центральная колонка: 3D рельефная золотая сургучная медаль --}}
                        <div class="tutor-cert-seal-col">
                            <div class="tutor-cert-gold-medal">
                                <div class="tutor-cert-gold-medal-inner">
                                    <div class="tutor-cert-medal-star">★</div>
                                    <div class="tutor-cert-medal-txt-top">ОБЩЕСТВЕННЫЙ</div>
                                    <div class="tutor-cert-medal-txt-main">МЕТОДИЧЕСКИЙ СОВЕТ</div>
                                    <div class="tutor-cert-medal-txt-sub">★ EDUSFERA ★</div>
                                </div>
                            </div>
                            <div class="tutor-cert-ribbons">
                                <div class="tutor-cert-ribbon-tail"></div>
                                <div class="tutor-cert-ribbon-tail tutor-cert-ribbon-tail--green"></div>
                            </div>
                        </div>

                        {{-- Правая колонка: дата и место выдачи --}}
                        <div class="tutor-cert-verify-col">
                            <div class="tutor-cert-issue-info">
                                <div>г. Минск, Республика Беларусь</div>
                                <div>Дата выдачи: <b>{{ $councilJoinedAt ? $councilJoinedAt->translatedFormat('d F Y г.') : now()->translatedFormat('d F Y г.') }}</b></div>
                                <div style="color:#059669; font-weight:700; margin-top:2px;">✓ Действительно бессрочно</div>
                            </div>
                        </div>
                    </div>

                    {{-- Нижняя полоса микрозащиты --}}
                    <div class="tutor-cert-bottom-security-bar">
                        <span>EDUSFERA DIGITAL EDUCATION SYSTEM</span>
                        <span>★ ОФИЦИАЛЬНЫЙ АТТЕСТАЦИОННЫЙ РЕЕСТР ПЕДАГОГОВ ★</span>
                        <span>РЕСПУБЛИКА БЕЛАРУСЬ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
