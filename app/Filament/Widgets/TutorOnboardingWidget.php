<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Models\Lesson;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Ai\GeminiService;
use App\Services\TutorVerificationService;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

class TutorOnboardingWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-onboarding-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public bool $showModal = false;

    public bool $showTariffModal = false;

    public bool $isYearly = false;

    public string $selectedPlan = 'pro';

    public ?string $promoCode = null;

    public ?string $appliedPromo = null;

    public ?string $promoMessage = null;

    public int $step = 1;

    public ?int $activeFormStep = null; // compatibility with legacy calls

    public ?string $verificationStatus = 'pending';

    public bool $isVerified = false;

    public string $subjectSearch = '';

    public string $activeSubjectCategory = 'all';

    // Step 1: Subjects, Pricing & Bio
    public bool $isPublic = false;

    public array $selectedSubjects = [];

    public string $pricePerHour = '45';

    public int $experienceYears = 3;

    public array $selectedSpecializations = [];

    public string $bio = '';

    public bool $isGeneratingBio = false;

    // Step 2: Working Hours & Days
    public string $activePreset = 'evenings';

    public array $availabilityDays = [1, 2, 3, 4, 5]; // Mon..Fri

    public string $startTime = '16:00';

    public string $endTime = '21:00';

    // Step 3: Activation & Profile Link
    public bool $isCompleted = false;

    public int $progress = 0;

    public bool $step1Done = false;

    public bool $step2Done = false;

    public bool $step3Done = false;

    public int $stepsCompletedCount = 0;

    public string $tutorSlug = '';

    public ?string $publicProfileUrl = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isTutor() ?? false;
    }

    /**
     * Все предметы школьной программы и ЦЭ/ЦТ Республики Беларусь, сгруппированные логично.
     */
    public static function getSubjectsByCategory(): array
    {
        return [
            'belarusian' => [
                'title' => 'Белорусский язык и литература',
                'icon' => '🇧🇾',
                'badge' => 'ЦЭ / ЦТ',
                'items' => [
                    'Белорусский язык',
                    'Белорусская литература',
                ],
            ],
            'exact' => [
                'title' => 'Точные науки и IT',
                'icon' => '📐',
                'badge' => 'ЦЭ / ЦТ',
                'items' => [
                    'Математика',
                    'Физика',
                    'Информатика',
                ],
            ],
            'languages' => [
                'title' => 'Русский и иностранные языки',
                'icon' => '🌍',
                'badge' => 'ЦЭ / ЦТ',
                'items' => [
                    'Русский язык',
                    'Русская литература',
                    'Английский язык',
                    'Немецкий язык',
                    'Французский язык',
                    'Испанский язык',
                    'Китайский язык',
                ],
            ],
            'nature' => [
                'title' => 'Естественные науки',
                'icon' => '🔬',
                'badge' => 'ЦЭ / ЦТ',
                'items' => [
                    'Химия',
                    'Биология',
                    'География',
                ],
            ],
            'humanities' => [
                'title' => 'Общественные науки и история',
                'icon' => '📜',
                'badge' => 'ЦЭ / ЦТ',
                'items' => [
                    'История Беларуси',
                    'Всемирная история',
                    'Обществоведение',
                ],
            ],
            'primary' => [
                'title' => 'Начальная школа и развитие',
                'icon' => '🎒',
                'badge' => '1–4 кл',
                'items' => [
                    'Начальная школа (1–4 классы)',
                    'Подготовка к школе',
                    'Логопед и дефектолог',
                ],
            ],
        ];
    }

    /**
     * Популярные предметы для быстрого выбора в 1 клик на самом верху.
     */
    public static function getPopularSubjects(): array
    {
        return [
            'Белорусский язык',
            'Математика',
            'Русский язык',
            'Английский язык',
            'Физика',
            'Химия',
            'Биология',
            'История Беларуси',
            'Обществоведение',
        ];
    }

    public static function getAllSubjectsList(): array
    {
        $all = [];
        foreach (self::getSubjectsByCategory() as $cat) {
            foreach ($cat['items'] as $item) {
                $all[] = $item;
            }
        }

        return array_values(array_unique($all));
    }

    public function refreshState(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isTutor()) {
            return;
        }

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();
        $hasAvailability = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        $this->step1Done = (bool) ($profile && ! empty($profile->subjects) && (float) $profile->price_per_hour > 0);
        $this->step2Done = (bool) $hasAvailability;
        $this->step3Done = (bool) ($profile && ! empty($profile->onboarding_completed_at));
        $this->isCompleted = $this->step3Done;

        $this->verificationStatus = $profile?->verification_status ?? 'pending';
        $this->isVerified = (bool) ($profile?->is_verified ?? false);

        $this->stepsCompletedCount = ($this->step1Done ? 1 : 0) + ($this->step2Done ? 1 : 0) + ($this->step3Done ? 1 : 0);
        $this->progress = (int) round(($this->stepsCompletedCount / 3) * 100);

        $nameSlug = $user->name ? \Illuminate\Support\Str::slug($user->name) : '';
        $this->tutorSlug = ! empty($nameSlug) ? $nameSlug : ($profile ? (string) $profile->id : (string) $user->id);
        $this->publicProfileUrl = $profile ? route('tutors.show', $profile->id) : url('/tutors/' . $user->id);
    }

    public function mount(): void
    {
        $this->refreshState();

        $user = auth()->user();
        if (! $user || ! $user->isTutor()) {
            return;
        }

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();
        $hasAvailability = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        $hasSubjectAndPrice = $profile && ! empty($profile->subjects) && (float) $profile->price_per_hour > 0;
        $this->isCompleted = (bool) ($profile && ! empty($profile->onboarding_completed_at));

        // Load existing profile values if present
        if ($profile) {
            $subs = $profile->subjects;
            $this->selectedSubjects = is_array($subs) ? $subs : (json_decode((string) $subs, true) ?: []);
            if ((float) $profile->price_per_hour > 0) {
                $this->pricePerHour = (string) (int) $profile->price_per_hour;
            }
            if ($profile->experience_years > 0) {
                $this->experienceYears = (int) $profile->experience_years;
            }
            $specs = $profile->exam_specializations;
            $this->selectedSpecializations = is_array($specs) ? $specs : (json_decode((string) $specs, true) ?: []);
            $this->bio = (string) ($profile->bio ?: '');
            $this->publicProfileUrl = route('tutors.show', $profile->id);
            $this->verificationStatus = $profile->verification_status ?? 'pending';
            $this->isVerified = (bool) $profile->is_verified;
            $this->isPublic = (bool) ($profile->is_public ?? false);
        } else {
            $this->selectedSubjects = [];
            $this->selectedSpecializations = [];
            $this->bio = '';
            $this->isPublic = false;
        }

        // Load existing availability times if present
        $firstAvail = TutorAvailability::query()->where('user_id', $user->id)->where('is_active', true)->first();
        if ($firstAvail) {
            $this->startTime = substr((string) $firstAvail->start_time, 0, 5);
            $this->endTime = substr((string) $firstAvail->end_time, 0, 5);
            $this->availabilityDays = TutorAvailability::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('day_of_week')
                ->all();
        }

        // Show wizard modal if onboarding is not completed and user hasn't explicitly dismissed this session
        $dismissed = session()->get('tutor_onboarding_modal_dismissed', false);
        if (! $this->isCompleted && ! $dismissed) {
            $this->showModal = true;
            if (! $hasSubjectAndPrice) {
                $this->step = 1;
            } elseif (! $hasAvailability) {
                $this->step = 2;
            } else {
                $this->step = 3;
            }
        }
    }

    public function setCategory(string $category): void
    {
        $this->activeSubjectCategory = $category;
    }

    public function toggleSubject(string $subject): void
    {
        if (in_array($subject, $this->selectedSubjects, true)) {
            $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subject]));
        } else {
            $this->selectedSubjects[] = $subject;
        }
    }

    public function removeSubject(string $subject): void
    {
        $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subject]));
    }

    public function setQuickPrice(string $price): void
    {
        $this->pricePerHour = $price;
    }

    public function toggleSpecialization(string $spec): void
    {
        if (in_array($spec, $this->selectedSpecializations, true)) {
            $this->selectedSpecializations = array_values(array_diff($this->selectedSpecializations, [$spec]));
        } else {
            $this->selectedSpecializations[] = $spec;
        }
    }

    public function generateAiBio(): void
    {
        $this->isGeneratingBio = true;
        $user = auth()->user();
        $name = $user?->name ?? 'Преподаватель';
        $subjectStr = ! empty($this->selectedSubjects) ? implode(', ', $this->selectedSubjects) : 'школьным предметам';
        $specsStr = ! empty($this->selectedSpecializations) ? implode(', ', $this->selectedSpecializations) : 'ЦТ/ЦЭ 2026';

        $prompt = "Напиши краткую, доброжелательную презентацию репетитора для карточки на сайте (1-2 емких предложения).\n".
            "Имя репетитора: {$name}.\n".
            "Предмет: {$subjectStr}.\n".
            "Направление: {$specsStr}.\n".
            "ВАЖНО:\n".
            "1. Выведи ТОЛЬКО готовый текст презентации для карточки.\n".
            "2. Никаких звездочек, сносок, кавычек или примечаний.\n".
            "3. СТРОЖАЙШЕ ЗАПРЕЩЕНО писать внизу подсчет слов (например: *20 слов*, **Количество слов**, (25 слов) и т.д.). Никаких метаданных и примечаний.";

        try {
            $generated = app(GeminiService::class)->generateText($prompt, 'Ты ведущий методист образовательной платформы Edusfera.by.');
            $clean = $this->cleanBioText((string) $generated);
            if (! empty($clean)) {
                $this->bio = $clean;
            } else {
                $this->bio = "Понятно и спокойно объясняю {$subjectStr}, готовлю к {$specsStr} на максимальный результат. Индивидуальный подход и поддержка на каждом занятии.";
            }
        } catch (\Throwable $e) {
            $this->bio = "Понятно и спокойно объясняю {$subjectStr}, готовлю к {$specsStr} на максимальный результат. Индивидуальный подход и поддержка на каждом занятии.";
        }

        $this->isGeneratingBio = false;
        Notification::make()->title('Визитка составлена с помощью ИИ')->success()->send();
    }

    protected function cleanBioText(string $text): string
    {
        // 1. Split lines to discard trailing word count footnotes or notes with asterisks
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $validLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Remove any line that mentions word counts or characters or asterisks footnotes
            if (preg_match('/^[\*\_\-\s]*(?:количество слов|кол-во слов|число слов|объем|слов|слова|words?|символов|знаков)[\s\:\d\*\_\(\)]*$/iu', $trimmed)) {
                continue;
            }
            if (preg_match('/^\*+.*?(?:слов|слова|слово).*?\*+$/iu', $trimmed)) {
                continue;
            }
            if (preg_match('/^\*+.*?\*+$/u', $trimmed) && mb_strlen($trimmed) < 60) {
                continue;
            }

            $validLines[] = $trimmed;
        }

        $joined = implode(' ', $validLines);

        // 2. Remove markdown asterisks from anywhere in text
        $joined = preg_replace('/\*{1,3}(.*?)\*{1,3}/u', '$1', $joined);
        $joined = str_replace('*', '', $joined);

        // 3. Remove inline parenthetical word counts: e.g. "(25 слов)", "(~20 слов)", "[15 слов]"
        $joined = preg_replace('/[\(\[\*]\s*~?\s*\d+\s*(?:слов|слова|слово|знаков)\s*[\)\]\*]/iu', '', $joined);

        // 4. Remove quote characters
        $joined = str_replace(['"', '«', '»', '`'], '', $joined);

        return trim(preg_replace('/\s+/', ' ', $joined));
    }

    public function saveStep1(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        if (empty($this->selectedSubjects)) {
            Notification::make()->title('Выберите хотя бы один предмет для преподавания')->warning()->send();

            return;
        }

        $price = max(10, (float) $this->pricePerHour);

        // Security / Moderation: Profile is saved with 'pending' verification status. It is NOT immediately verified!
        $profile = TutorProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'is_public' => $this->isPublic,
                'subjects' => array_values(array_unique($this->selectedSubjects)),
                'price_per_hour' => $price,
                'experience_years' => max(0, $this->experienceYears),
                'exam_specializations' => array_values(array_unique($this->selectedSpecializations)),
                'bio' => trim($this->bio) ?: ('Преподаватель по предмету ' . implode(', ', $this->selectedSubjects) . '. Индивидуальный подход и подготовка к экзаменам.'),
                'verification_status' => 'pending',
                'is_verified' => false,
            ]
        );

        $this->publicProfileUrl = route('tutors.show', $profile->id);
        $this->refreshState();
        $this->step = 2;
        $this->activeFormStep = 2;

        Notification::make()
            ->title('Предметы и ставка сохранены!')
            ->body('Шаг 1 из 3 пройден. Теперь укажите удобные рабочие часы для занятий.')
            ->success()
            ->send();
    }

    public function applyPreset(string $preset): void
    {
        $this->activePreset = $preset;

        if ($preset === 'evenings') {
            $this->availabilityDays = [1, 2, 3, 4, 5]; // Mon..Fri
            $this->startTime = '16:00';
            $this->endTime = '21:00';
        } elseif ($preset === 'everyday') {
            $this->availabilityDays = [1, 2, 3, 4, 5, 6, 0]; // Mon..Sun
            $this->startTime = '10:00';
            $this->endTime = '20:00';
        } elseif ($preset === 'weekends') {
            $this->availabilityDays = [6, 0]; // Sat..Sun
            $this->startTime = '10:00';
            $this->endTime = '18:00';
        }
    }

    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->availabilityDays, true)) {
            $this->availabilityDays = array_values(array_diff($this->availabilityDays, [$day]));
        } else {
            $this->availabilityDays[] = $day;
            sort($this->availabilityDays);
        }
        $this->activePreset = 'custom';
    }

    public function saveStep2(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        if (empty($this->availabilityDays)) {
            Notification::make()->title('Выберите хотя бы один рабочий день недели')->warning()->send();

            return;
        }

        if ($this->startTime >= $this->endTime) {
            Notification::make()->title('Время окончания должно быть позже времени начала')->warning()->send();

            return;
        }

        // Delete old availability and recreate for all 7 days
        TutorAvailability::query()->where('user_id', $user->id)->delete();

        $allDays = [1, 2, 3, 4, 5, 6, 0];
        foreach ($allDays as $day) {
            $isActive = in_array($day, $this->availabilityDays, true);
            TutorAvailability::query()->create([
                'user_id' => $user->id,
                'day_of_week' => $day,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'is_active' => $isActive,
            ]);
        }

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();
        if ($profile) {
            $this->publicProfileUrl = route('tutors.show', $profile->id);
        }

        $this->refreshState();
        $this->step = 3;
        $this->activeFormStep = 3;

        Notification::make()
            ->title('Расписание сохранено!')
            ->body('Шаг 2 из 3 пройден. Проверьте анкету и отправьте её на модерацию.')
            ->success()
            ->send();
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->activeFormStep = $this->step;
    }

    public function goToStep(int $targetStep): void
    {
        if ($targetStep >= 1 && $targetStep <= 3) {
            $this->step = $targetStep;
            $this->activeFormStep = $targetStep;
        }
    }

    public function openStep(int $step): void
    {
        $this->goToStep($step);
        $this->showModal = true;
    }

    public function backToHub(): void
    {
        $this->step = 1;
        $this->activeFormStep = null;
    }

    public function openModal(?int $toStep = null): void
    {
        if ($toStep !== null) {
            $this->step = max(1, min(3, $toStep));
            $this->activeFormStep = $this->step;
        } else {
            $this->step = 1;
            $this->activeFormStep = 1;
        }
        $this->showModal = true;
        session()->forget('tutor_onboarding_modal_dismissed');
    }

    public function dismissModal(): void
    {
        $this->showModal = false;
        session()->put('tutor_onboarding_modal_dismissed', true);
    }

    /**
     * Завершение шага 3:
     * 1. Анкета уходит на модерацию техническому администратору (verification_status = 'pending', is_verified = false).
     * 2. Окно 3 шагов исчезает ($showModal = false).
     * 3. Появляется меню выбора тарифа ($showTariffModal = true).
     */
    public function finishOnboarding(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();
        if ($profile) {
            app(TutorVerificationService::class)->submitForReview($profile);
        }

        $this->refreshState();
        $this->isCompleted = true;
        $this->verificationStatus = 'pending';
        $this->isVerified = false;
        $this->showModal = false; // Step 3 menu disappears!
        $this->step = 3;
        $this->activeFormStep = null;
        $this->progress = 100;
        $this->showTariffModal = true; // Tariff selection and subscription activation modal appears!

        session()->forget('tutor_onboarding_modal_dismissed');

        Notification::make()
            ->title('🛡️ Анкета отправлена на модерацию!')
            ->body('Шаг 3 пройден! Данные переданы техническому администратору. Теперь выберите тарифный план и активируйте бесплатный пробный период.')
            ->success()
            ->send();
    }

    public function toggleBillingCycle(): void
    {
        $this->isYearly = ! $this->isYearly;
    }

    public function selectPlan(string $plan): void
    {
        $this->selectedPlan = $plan;
    }

    public function openTariffModal(): void
    {
        $this->showTariffModal = true;
    }

    public function dismissTariffModal(): void
    {
        $this->showTariffModal = false;
    }

    public function activateSubscription(string $planName): void
    {
        $this->showTariffModal = false;
        $this->redirect(route('filament.admin.pages.tutor-subscription-page'));
    }

    public function applyPromoCode(): void
    {
        $code = strtoupper(trim((string) $this->promoCode));
        if (empty($code)) {
            $this->promoMessage = 'Пожалуйста, введите промокод';
            Notification::make()->title('Введите промокод')->warning()->send();

            return;
        }

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $promo = PromoCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $promo) {
            $this->promoMessage = 'Промокод не найден или срок его действия истёк';
            Notification::make()
                ->title('Промокод не найден')
                ->body('Проверьте правильность промокода или выберите один из тарифов.')
                ->warning()
                ->send();

            return;
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            $this->promoMessage = 'Промокод ещё не активен';
            Notification::make()->title('Промокод ещё не активен')->warning()->send();

            return;
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            $this->promoMessage = 'Срок действия промокода истёк';
            Notification::make()->title('Срок действия промокода истёк')->warning()->send();

            return;
        }

        if ($promo->max_uses !== null && $promo->used_count >= $promo->max_uses) {
            $this->promoMessage = 'Лимит активаций этого промокода исчерпан';
            Notification::make()->title('Лимит активаций промокода исчерпан')->warning()->send();

            return;
        }

        $isLifetime = $promo->isLifetimeSubscription();
        $months = $promo->getSubscriptionMonths();
        $planValue = ($promo->subscription_plan && $promo->subscription_plan !== 'any')
            ? $promo->subscription_plan
            : 'pro';
        $planEnum = SubscriptionPlan::tryFrom($planValue) ?? SubscriptionPlan::PRO;

        $now = CarbonImmutable::now();
        $newEnd = $isLifetime
            ? CarbonImmutable::create(2037, 12, 31, 23, 59, 59)
            : $now->addMonths($months);

        // 1. Активируем подписку напрямую без 14-дневного триала
        Subscription::updateOrCreate(
            ['tutor_id' => $user->id],
            [
                'plan' => $planEnum,
                'status' => SubscriptionStatus::ACTIVE,
                'is_founder' => false,
                'trial_ends_at' => null, // Важно: 14 дней триала не активируется, подписка действует по промокоду сразу
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $newEnd,
                'grace_period_ends_at' => null,
                'is_onboarded' => true,
                'responses_used_this_month' => 0,
            ]
        );

        // 2. Логируем использование промокода
        try {
            $promo->increment('used_count');
            PromoCodeUsage::create([
                'promo_code_id' => $promo->id,
                'user_id' => $user->id,
                'lesson_id' => null,
                'transaction_id' => null,
                'order_type' => 'subscription',
                'discount_amount' => $planEnum->monthlyPriceByn() * $months,
                'original_amount' => $planEnum->monthlyPriceByn() * $months,
                'final_amount' => 0.0,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('PromoCodeUsage logging failed in onboarding: '.$e->getMessage());
        }

        // 3. Формируем понятный текст условий промокода
        if ($isLifetime) {
            $conditionText = 'бессрочный полный доступ без ограничений';
        } elseif ($promo->discount_type === 'free_period' || $promo->isFreeSubscription()) {
            $periodStr = match ($promo->subscription_period) {
                '3_months' => '3 месяца',
                '6_months' => '6 месяцев',
                '12_months' => '1 год',
                default => $months === 1 ? '1 месяц' : "{$months} мес.",
            };
            $conditionText = "бесплатный доступ на {$periodStr} (до {$newEnd->format('d.m.Y')})";
        } else {
            $conditionText = "скидка {$promo->discount_value}% на {$months} мес.";
        }

        // 4. Меню закрывается, рабочий стол кабинета открывается
        $profile = TutorProfile::query()->where('user_id', $user->id)->first();
        if ($profile) {
            if (! $profile->onboarding_completed_at) {
                $profile->update(['onboarding_completed_at' => now()]);
            }
        } else {
            TutorProfile::query()->create([
                'user_id' => $user->id,
                'subjects' => $this->selectedSubjects,
                'price_per_hour' => max(10, (float) $this->pricePerHour),
                'experience_years' => max(0, $this->experienceYears),
                'exam_specializations' => $this->selectedSpecializations,
                'bio' => $this->bio,
                'verification_status' => 'pending',
                'is_verified' => false,
                'onboarding_completed_at' => now(),
            ]);
        }

        $this->showTariffModal = false;
        $this->showModal = false;
        $this->isCompleted = true;
        $this->appliedPromo = $code;
        $this->promoMessage = "✓ Промокод {$code} успешно применён!";
        $this->refreshState();

        // 5. Уведомление с условиями промокода
        Notification::make()
            ->title("🎉 Подписка {$planEnum->title()} успешно активирована!")
            ->body("Промокод «{$code}» успешно применён! Условия: {$conditionText}. Пробный период 14 дней отключен — тариф активирован сразу. Добро пожаловать в личный кабинет!")
            ->success()
            ->send();
    }

    public function startTestClassroom(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $demoStudent = User::query()->firstOrCreate(
            ['email' => 'demo-student@edusfera.by'],
            [
                'name' => 'Демо Ученик',
                'phone' => '+375290000000',
                'role' => 'student',
                'password' => bcrypt('demo-pass-2026'),
            ]
        );

        $testLesson = Lesson::query()->firstOrCreate(
            [
                'tutor_id' => $user->id,
                'package_code' => 'sandbox_test',
                'status' => Lesson::STATUS_CONFIRMED,
            ],
            [
                'student_id' => $demoStudent->id,
                'start_time' => now('UTC')->subMinutes(5),
                'end_time' => now('UTC')->addHours(2),
                'duration_minutes' => 60,
                'price' => '0.00',
                'platform_commission' => '0.00',
                'net_amount' => '0.00',
                'payment_status' => Lesson::PAYMENT_PAID,
            ]
        );

        $this->redirect(route('classroom.show', $testLesson));
    }

    public function getOnboardingInitialData(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $profile = TutorProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $subscription = Subscription::where('tutor_id', $user->id)->first();
        $availabilities = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $activeDays = $availabilities->pluck('day_of_week')->all();
        if (empty($activeDays)) {
            $activeDays = [1, 2, 3, 4, 5];
        }

        $firstAvail = $availabilities->first();
        $startTime = $firstAvail ? substr((string) $firstAvail->start_time, 0, 5) : '16:00';
        $endTime = $firstAvail ? substr((string) $firstAvail->end_time, 0, 5) : '21:00';

        $subjects = $profile->subjects ?: [];
        if (is_string($subjects)) {
            $subjects = json_decode($subjects, true) ?: [];
        }

        $specializations = $profile->exam_specializations ?: [];
        if (is_string($specializations)) {
            $specializations = json_decode($specializations, true) ?: [];
        }

        $isOnboarded = (bool) ($subscription?->is_onboarded || $profile->onboarding_completed_at);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $profile->avatar_url,
            ],
            'profile' => [
                'id' => $profile->id,
                'subjects' => $subjects,
                'price_per_hour' => (float) ($profile->price_per_hour ?: 45),
                'experience_years' => (int) ($profile->experience_years ?: 3),
                'bio' => (string) ($profile->bio ?: ''),
                'exam_specializations' => $specializations,
                'avatar_url' => $profile->avatar_url,
                'verification_status' => $profile->verification_status ?? 'pending',
                'is_verified' => (bool) $profile->is_verified,
                'is_onboarded' => $isOnboarded,
                'public_profile_url' => route('tutors.show', $profile->id),
            ],
            'schedule' => [
                'days' => $activeDays,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ],
            'subscription' => [
                'plan' => $subscription?->plan?->value ?? 'pro',
                'status' => $subscription?->status?->value ?? 'trial',
                'is_onboarded' => $isOnboarded,
                'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            ],
            'catalog' => [
                'categories' => self::getSubjectsByCategory(),
                'popular_subjects' => self::getPopularSubjects(),
            ],
        ];
    }
}
