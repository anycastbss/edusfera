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
</div>
