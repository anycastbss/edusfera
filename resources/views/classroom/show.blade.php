@php
    $classroomConfig = $classroomConfig ?? [
        'lessonId' => $lesson->id,
        'subject' => $lesson->subject ?? 'Онлайн-урок',
        'topic' => $lesson->topic ?? '',
        'userName' => $userRole === 'tutor' ? ($lesson->tutor->name ?? 'Преподаватель') : ($lesson->student->name ?? 'Ученик'),
        'partnerName' => $userRole === 'tutor' ? ($lesson->student->name ?? 'Ученик') : ($lesson->tutor->name ?? 'Преподаватель'),
        'userRole' => $userRole,
        'userId' => auth()->id(),
        'isBoardLocked' => (bool) ($session->is_board_locked ?? false),
        'studentInviteUrl' => $studentInviteUrl ?? '',
        'csrfToken' => csrf_token(),
        'liveKitConfigured' => (bool) ($liveKitConfigured ?? false),
        'liveKitToken' => $liveKitToken ?? null,
        'liveKitWsUrl' => $liveKitWsUrl ?? null,
        'jitsiDomain' => $jitsiDomain ?? 'meet.jit.si',
        'jitsiRoomName' => $jitsiRoomName ?? ('edusfera_' . $lesson->id),
        'mediaServerUrl' => $mediaServerUrl ?? null,
        'iceServers' => $iceServers ?? [],
        'maxLessonDurationMinutes' => $maxLessonDurationMinutes ?? 60,
        'aiChatHistory' => $session->meta['ai_chat_history'] ?? [],
        'exitUrl' => $userRole === 'tutor' ? route('filament.admin.resources.lessons.index') : url('/'),
    ];
@endphp
<!DOCTYPE html>
<html lang="ru" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <title>{{ !empty($isDemo) ? 'Виртуальный класс PRO (Демо-песочница) — Edusfera' : 'Виртуальный класс — ' . ($lesson->subject ?? 'Онлайн-урок') }}</title>
    <meta name="description" content="Виртуальный класс Edusfera в стиле Excalidraw — интерактивная доска, видеосвязь, чат и задания">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/classroom.css', 'resources/js/classroom-app.jsx'])
</head>
<body class="theme-light" style="margin: 0; padding: 0; width: 100vw; height: 100dvh; max-height: 100dvh; overflow: hidden; background-color: #FFFFFF; position: fixed; inset: 0;">

    <div id="classroom-app-root" style="width: 100vw; height: 100dvh; max-height: 100dvh; overflow: hidden; position: fixed; inset: 0;" data-config="{{ json_encode($classroomConfig, JSON_UNESCAPED_UNICODE) }}"></div>

    <script>
        window.__classroom = @json($classroomConfig);
    </script>
</body>
</html>
