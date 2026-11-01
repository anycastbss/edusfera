<!DOCTYPE html>
<html lang="ru" class="h-full bg-[#090A10] text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $reason === 'pending' ? 'Занятие ожидает подтверждения' : ($reason === 'too_early' ? 'До начала занятия' : 'Занятие завершено') }} — Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --cr-bg: #090a10;
            --cr-card-bg: rgba(22, 27, 44, 0.7);
            --cr-card-border: rgba(255, 255, 255, 0.12);
            --cr-accent-purple: #7D39EB;
            --cr-accent-lime: #C6FF33;
            --cr-accent-cyan: #38BDF8;
            --cr-text-muted: #94A3B8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--cr-bg);
            color: #FFFFFF;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Background */
        .ambient-glow {
            position: fixed;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.18;
            pointer-events: none;
            z-index: 0;
        }
        .glow-1 {
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            background: radial-gradient(circle, var(--cr-accent-purple) 0%, transparent 70%);
        }
        .glow-2 {
            bottom: -100px;
            right: 10%;
            background: radial-gradient(circle, var(--cr-accent-lime) 0%, transparent 70%);
            opacity: 0.12;
        }

        .waiting-card {
            position: relative;
            z-index: 10;
            max-width: 540px;
            width: 100%;
            background: var(--cr-card-bg);
            border: 1px solid var(--cr-card-border);
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 40px 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 24px;
        }
        .badge--yellow {
            background: rgba(234, 179, 8, 0.15);
            border: 1px solid rgba(234, 179, 8, 0.4);
            color: #FACC15;
        }
        .badge--blue {
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: #38BDF8;
        }
        .badge--gray {
            background: rgba(148, 163, 184, 0.15);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #CBD5E1;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 10px currentColor;
            animation: pulse-kf 1.6s ease-in-out infinite;
        }
        @keyframes pulse-kf {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.5; }
        }

        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin-bottom: 12px;
            color: #FFFFFF;
        }
        p.subtitle {
            font-size: 15px;
            color: var(--cr-text-muted);
            line-height: 1.5;
            margin-bottom: 28px;
        }

        /* Lesson Details Box */
        .info-box {
            background: rgba(10, 14, 26, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 16px 20px;
            margin-bottom: 28px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        .info-label {
            color: var(--cr-text-muted);
            font-weight: 500;
        }
        .info-val {
            color: #FFFFFF;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Action Buttons */
        .actions-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 48px;
            padding: 0 24px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            width: 100%;
        }
        .btn--primary {
            background: var(--cr-accent-purple);
            color: #FFFFFF;
            box-shadow: 0 8px 24px rgba(125, 57, 235, 0.35);
        }
        .btn--primary:hover {
            background: #6D28D9;
            transform: translateY(-1px);
        }
        .btn--lime {
            background: var(--cr-accent-lime);
            color: #0F172A;
            box-shadow: 0 8px 24px rgba(198, 255, 51, 0.3);
        }
        .btn--lime:hover {
            background: #B3F020;
            transform: translateY(-1px);
        }
        .btn--secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .btn--secondary:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .live-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            color: #94A3B8;
            margin-top: 18px;
        }
        .live-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: var(--cr-accent-purple);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>

    <div class="waiting-card">
        @if ($reason === 'pending')
            <div class="badge badge--yellow">
                <span class="pulse-dot"></span>
                <span>Ожидает подтверждения</span>
            </div>

            <div class="icon-circle">⏳</div>

            @if ($isTutor)
                <h1>Новая заявка на урок #{{ $lesson->id }}</h1>
                <p class="subtitle">Ученик ожидает вашего подтверждения. Нажмите кнопку ниже, чтобы принять заявку и мгновенно войти в виртуальный класс.</p>
            @else
                <h1>Урок ожидает подтверждения</h1>
                <p class="subtitle">Преподаватель {{ $lesson->tutor->name ?? 'репетитор' }} получил вашу заявку. Как только занятие будет подтверждено, класс откроется автоматически.</p>
            @endif

        @elseif ($reason === 'too_early')
            <div class="badge badge--blue">
                <span class="pulse-dot"></span>
                <span>До начала урока</span>
            </div>

            <div class="icon-circle">⏰</div>

            <h1>До начала урока еще есть время</h1>
            <p class="subtitle">
                Занятие запланировано на {{ $lesson->start_time ? $lesson->start_time->format('d.m.Y в H:i') : 'указанное время' }}. Вход в виртуальный класс откроется за 2 часа до начала.
            </p>

        @else
            <div class="badge badge--gray">
                <span>Завершено</span>
            </div>

            <div class="icon-circle">🏁</div>

            <h1>Занятие завершено</h1>
            <p class="subtitle">Этот урок уже окончен. Вы можете связаться с преподавателем в чате или запланировать следующее занятие.</p>
        @endif

        <!-- Lesson Meta Card -->
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Преподаватель</span>
                <span class="info-val">{{ $lesson->tutor->name ?? 'Не указан' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ученик</span>
                <span class="info-val">{{ $lesson->student->name ?? 'Не указан' }}</span>
            </div>
            @if ($lesson->start_time)
            <div class="info-row">
                <span class="info-label">Дата и время</span>
                <span class="info-val">{{ $lesson->start_time->format('d.m.Y H:i') }}</span>
            </div>
            @endif
            @if ($lesson->price > 0)
            <div class="info-row">
                <span class="info-label">Стоимость</span>
                <span class="info-val">{{ number_format((float)$lesson->price, 2) }} BYN</span>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="actions-col">
            @if ($isTutor && $reason === 'pending')
                <!-- Tutor 1-Click Confirmation -->
                <a href="{{ route('classroom.show', ['lesson' => $lesson->id, 'confirm' => 1]) }}" class="btn btn--lime">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Подтвердить и открыть класс</span>
                </a>
            @endif

            <!-- Direct Chat with Partner -->
            <form action="{{ route('lessons.conversation', $lesson) }}" method="POST" style="width: 100%;">
                @csrf
                <button type="submit" class="btn btn--secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span>Написать в чат</span>
                </button>
            </form>

            <!-- Dashboard Back Link -->
            <a href="/admin/lessons" class="btn btn--secondary" style="background: transparent; border-color: rgba(255, 255, 255, 0.08);">
                <span>← Вернуться к занятиям</span>
            </a>
        </div>

        @if (!$isTutor && ($reason === 'pending' || $reason === 'too_early'))
            <div class="live-status">
                <div class="live-spinner"></div>
                <span id="cr-status-text">Ожидаем подтверждения в реальном времени...</span>
            </div>
        @endif
    </div>

    @if (!$isTutor && ($reason === 'pending' || $reason === 'too_early'))
    <script>
        (function() {
            var lessonId = {{ (int) $lesson->id }};
            var statusUrl = '/classroom/' + lessonId + '/status';
            var interval = setInterval(async function() {
                try {
                    var res = await fetch(statusUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        var data = await res.json();
                        if (data.can_enter) {
                            clearInterval(interval);
                            var statusEl = document.getElementById('cr-status-text');
                            if (statusEl) statusEl.textContent = 'Урок подтвержден! Подключаем к классу...';
                            window.location.href = data.redirect_url || ('/classroom/' + lessonId);
                        }
                    }
                } catch (e) {}
            }, 3500);
        })();
    </script>
    @endif
</body>
</html>
