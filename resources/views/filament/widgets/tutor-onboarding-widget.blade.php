<div class="edusfera-onboarding-root w-full mb-6">
    @php
        $initialData = $this->getOnboardingInitialData();
        $isCompleted = (bool) ($initialData['profile']['is_onboarded'] ?? false);
        $verificationStatus = $initialData['profile']['verification_status'] ?? 'pending';
        $isVerified = (bool) ($initialData['profile']['is_verified'] ?? false);
        $publicProfileUrl = $initialData['profile']['public_profile_url'] ?? url('/admin');
    @endphp

    @if(!$isCompleted)
        {{-- Banner prompting tutor to complete onboarding on dedicated page --}}
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border border-amber-300/70 dark:border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 sm:p-5 shadow-xs transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 flex-1 min-w-0">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 shadow-xs bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>

                    <div class="space-y-1 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-gray-950 dark:text-white">
                                Завершите оформление анкеты репетитора
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30">
                                Требуется заполнение
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed max-w-2xl">
                            Для публикации в каталоге и старта занятий укажите предметы, стоимость часа, расписание и выберите тариф.
                        </p>
                    </div>
                </div>

                <div class="shrink-0 self-start sm:self-center">
                    <a
                        href="{{ route('tutor.onboarding') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#bbf028] text-gray-950 font-bold text-xs sm:text-sm shadow-xs transition-all whitespace-nowrap"
                    >
                        <span>Заполнить анкету</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Dashboard Status Banner after Onboarding is complete --}}
        @php
            $profileUser = auth()->user();
            $tutorProfileModel = $profileUser?->tutorProfile;
            $isPrivate = (bool) ($tutorProfileModel ? ! $tutorProfileModel->is_public : false);
        @endphp
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border {{ $isPrivate ? 'border-slate-300 dark:border-slate-700 bg-gradient-to-r from-slate-100/80 via-slate-50 to-transparent dark:from-slate-800/40 dark:via-slate-900/20' : ($isVerified ? 'border-emerald-300/70 dark:border-emerald-500/30 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent' : 'border-amber-300/70 dark:border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent') }} p-4 sm:p-5 shadow-xs transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6">
                <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 flex-1 min-w-0">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 shadow-xs {{ $isPrivate ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' : ($isVerified ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' : 'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30') }}">
                        @if($isPrivate)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        @elseif($isVerified)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>

                    <div class="space-y-1 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-gray-950 dark:text-white">
                                @if($isPrivate)
                                    Диплом подтверждён. Анкета скрыта из каталога (Приватный режим)
                                @elseif($isVerified)
                                    Профиль проверен и опубликован в каталоге
                                @else
                                    Анкета отправлена на модерацию
                                @endif
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $isPrivate ? 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600' : ($isVerified ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30') }}">
                                {{ $isPrivate ? 'Приватный режим' : ($isVerified ? 'Активен' : 'На проверке') }}
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed max-w-2xl">
                            @if($isPrivate)
                                Ваша анкета надёжно скрыта от посторонних и поисковиков. Заниматься и бронировать уроки могут только те ученики, которым вы лично отправили прямую ссылку.
                            @elseif($isVerified)
                                Ученики и родители могут находить вас в каталоге репетиторов и записываться на уроки. Статус диплома подтверждён.
                            @else
                                Администратор проверяет фото, предметы и документы (обычно 2–4 часа). Вы уже можете вести занятия и приглашать своих учеников.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
                    <a
                        href="{{ route('tutor.onboarding', ['edit' => 1]) }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs sm:text-sm border border-gray-200 dark:border-gray-700 shadow-xs transition-colors whitespace-nowrap"
                    >
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Настройки анкеты</span>
                    </a>

                    @if(! $isPrivate && $isVerified)
                        <a
                            href="{{ $publicProfileUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#C6FF33] hover:bg-[#bbf028] text-gray-950 font-bold text-xs sm:text-sm shadow-xs transition-all whitespace-nowrap"
                        >
                            <span>В каталог</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tariff Selection Modal (Рисунок А.9 — Антикризисные офферы) --}}
    @if($showTariffModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/75 backdrop-blur-sm animate-fade-in">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black tracking-wide uppercase bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/30">
                            ⚡ Антикризисные тарифы
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-gray-950 dark:text-white mt-2">
                            Выберите формат старта практики
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Мгновенная активация через эквайринг Альфа-Банка. Полный доступ без блокировок.
                        </p>
                    </div>
                    <button wire:click="dismissTariffModal" type="button" class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition">
                        ✕
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    {{-- OFFER 1: Тест-драйв --}}
                    <div class="p-5 rounded-2xl border-2 border-emerald-500/80 bg-emerald-50/40 dark:bg-emerald-950/20 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/30 text-emerald-800 dark:text-emerald-300">
                                    ⚡ Быстрый старт
                                </span>
                                <div class="text-right">
                                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 leading-none">5 BYN</div>
                                    <div class="text-[10px] text-gray-500">сразу за 7 дней</div>
                                </div>
                            </div>
                            <h3 class="font-extrabold text-base text-gray-950 dark:text-white mb-2">
                                Тариф «Тест-драйв» (7 дней)
                            </h3>
                            <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-2 mb-4">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span>Полный доступ к ИИ-методисту (генерация вариантов ЦЭ/ЦТ)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span>Интерактивный класс LiveKit + Excalidraw</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span>Выгрузка чеков НПД для МНС РБ в 1 клик</span>
                                </li>
                                <li class="flex items-start gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span>•</span>
                                    <span>Через 7 дней — автопродление (40 BYN/мес Pro), отмена в 1 клик</span>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('filament.admin.pages.tutor-subscription-page') }}" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm text-center shadow-sm transition">
                            Подключить за 5 BYN →
                        </a>
                    </div>

                    {{-- OFFER 2: Клуб основателей --}}
                    <div class="p-5 rounded-2xl border-2 border-amber-500/80 bg-amber-50/40 dark:bg-amber-950/20 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-500/30 text-amber-800 dark:text-amber-300">
                                    👑 Лимит 10 мест
                                </span>
                                <div class="text-right">
                                    <div class="text-xs line-through text-gray-400">120 BYN</div>
                                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 leading-none">49 BYN</div>
                                    <div class="text-[10px] text-gray-500">за 3 месяца Pro</div>
                                </div>
                            </div>
                            <h3 class="font-extrabold text-base text-gray-950 dark:text-white mb-2">
                                «Клуб основателей» (3 мес. Pro)
                            </h3>
                            <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-2 mb-4">
                                <li class="flex items-start gap-2">
                                    <span class="text-amber-600 font-bold">✓</span>
                                    <span><strong>3 месяца тарифа Pro</strong> со скидкой 60%</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-amber-600 font-bold">✓</span>
                                    <span>Пожизненный статус Основателя платформы</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-amber-600 font-bold">✓</span>
                                    <span>Безлимитный ИИ, класс LiveKit и чеки НПД</span>
                                </li>
                                <li class="flex items-start gap-2 text-amber-700 dark:text-amber-400 font-semibold">
                                    <span>🔒</span>
                                    <span>Доступно только 10 мест</span>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('filament.admin.pages.tutor-subscription-page') }}" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs sm:text-sm text-center shadow-sm transition">
                            Вступить за 49 BYN →
                        </a>
                    </div>
                </div>

                <div class="text-center">
                    <button wire:click="dismissTariffModal" type="button" class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-300 underline">
                        Вернуться в панель управления
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
