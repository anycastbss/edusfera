@php
    $currentUser = auth()->user();
    if (!$currentUser) return;
    $isTutor = $currentUser->isTutor();
    $subject = $currentUser->subject ?? ($currentUser->tutorProfile?->subject ?? 'Математика');

    // Не выводим плавающий виджет на странице сообщений (чтобы не перекрывал кнопку отправки и поле ввода)
    if (request()->routeIs('*messages*') || request()->is('admin/messages*')) {
        return;
    }
@endphp

@viteReactRefresh
@vite(['resources/css/app.css', 'resources/js/app.jsx'])

<div id="floating-ai-copilot-root" class="hidden md:block"></div>

<script>
    window.EDUSFERA_COPILOT_CONFIG = {
        user: {
            id: {{ (int) $currentUser->id }},
            name: @json($currentUser->name),
            email: @json($currentUser->email),
            isTutor: {{ $isTutor ? 'true' : 'false' }},
            subject: @json($subject),
        },
        csrfToken: '{{ csrf_token() }}',
        endpoint: '/admin/ai-copilot/chat'
    };
</script>
