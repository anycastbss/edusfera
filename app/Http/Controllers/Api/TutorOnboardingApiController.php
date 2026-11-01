<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Filament\Widgets\TutorOnboardingWidget;
use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Ai\GeminiService;
use App\Services\PromoCodeService;
use App\Services\TutorVerificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TutorOnboardingApiController extends Controller
{
    use \App\Traits\DetectsMobileDevice;

    /**
     * Полноэкранная JSX-страница онбординга для нового репетитора.
     */
    public function showOnboardingPage(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return redirect('/login');
        }

        if ($user->isStudent()) {
            return app(StudentOnboardingApiController::class)->showOnboardingPage($request);
        }

        if (! $user->isTutor()) {
            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        $subscription = Subscription::where('tutor_id', $user->id)->first();
        $profile = TutorProfile::where('user_id', $user->id)->first();

        // Если репетитор уже прошёл онбординг и не перешёл для явного редактирования (?edit=1), сразу пускаем в кабинет
        if (! $request->boolean('edit') && ! $request->has('edit') && $subscription?->is_onboarded && $profile?->onboarding_completed_at) {
            return redirect($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin');
        }

        $initialData = $this->getState($request)->getData(true);

        return view('auth.onboarding', [
            'initialData' => $initialData,
        ]);
    }

    /**
     * Получить полное текущее состояние анкеты репетитора, доступные предметы и тарифы.
     */
    public function getState(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Доступно только для преподавателей.'], 403);
        }

        $profile = TutorProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $subscription = Subscription::where('tutor_id', $user->id)->first();

        // Get availability
        $availabilities = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $activeDays = $availabilities->pluck('day_of_week')->all();
        if (empty($activeDays)) {
            $activeDays = [1, 2, 3, 4, 5]; // default Mon-Fri
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

        return response()->json([
            'success' => true,
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
                'is_public' => (bool) ($profile->is_public ?? false),
                'public_profile_url' => route('tutors.show', $profile->id),
            ],
            'schedule' => [
                'days' => $activeDays,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ],
            'subscription' => [
                'plan' => $subscription?->plan?->value ?? 'pro',
                'plan_title' => $subscription?->plan?->title() ?? 'Pro',
                'status' => $subscription?->status?->value ?? 'trial',
                'status_title' => $subscription?->status?->title() ?? 'Пробный период',
                'is_active' => $subscription?->isActive() ?? false,
                'is_trial' => $subscription?->isInTrial() ?? false,
                'is_onboarded' => $isOnboarded,
                'is_lifetime' => (bool) ($subscription?->current_period_ends_at && $subscription->current_period_ends_at->year >= 2035),
                'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
                'current_period_ends_at' => $subscription?->current_period_ends_at?->toIso8601String(),
                'formatted_end_date' => $subscription?->current_period_ends_at?->format('d.m.Y'),
                'days_remaining' => $subscription?->daysRemaining() ?? 0,
            ],
            'catalog' => [
                'categories' => TutorOnboardingWidget::getSubjectsByCategory(),
                'popular_subjects' => TutorOnboardingWidget::getPopularSubjects(),
            ],
            'plans' => [
                'start' => [
                    'code' => 'start',
                    'title' => 'Стандарт',
                    'monthly_price' => SubscriptionPlan::START->monthlyPriceByn(),
                    'yearly_monthly_price' => round(SubscriptionPlan::START->yearlyPriceByn() / 12, 1),
                    'yearly_total' => SubscriptionPlan::START->yearlyPriceByn(),
                    'features' => [
                        'До 5 активных учеников',
                        'Приватный кабинет (скрыт из каталога)',
                        'Виртуальный класс SFU (до 60 мин)',
                        'Расписание и CRM учеников',
                        '0 генераций ИИ · Ручной учет налогов',
                    ],
                ],
                'pro' => [
                    'code' => 'pro',
                    'title' => 'Про (Популярный)',
                    'is_popular' => true,
                    'monthly_price' => SubscriptionPlan::PRO->monthlyPriceByn(),
                    'yearly_monthly_price' => round(SubscriptionPlan::PRO->yearlyPriceByn() / 12, 1),
                    'yearly_total' => SubscriptionPlan::PRO->yearlyPriceByn(),
                    'features' => [
                        'До 15 активных учеников',
                        '50 генераций ИИ в месяц (Сборник ЦТ/ЦЭ)',
                        'Авто-чеки НПД для МНС РБ в 1 клик',
                        'Уроки без ограничений по времени',
                        'Выбор: Приватный или Каталог репетиторов',
                        'До 10 откликов в месяц на бирже заявок',
                    ],
                ],
                'premium' => [
                    'code' => 'premium',
                    'title' => 'Премиум',
                    'monthly_price' => SubscriptionPlan::PREMIUM->monthlyPriceByn(),
                    'yearly_monthly_price' => round(SubscriptionPlan::PREMIUM->yearlyPriceByn() / 12, 1),
                    'yearly_total' => SubscriptionPlan::PREMIUM->yearlyPriceByn(),
                    'features' => [
                        'БЕЗЛИМИТНО учеников (Неограниченно)',
                        'Режим «Личная школа» (White-Label)',
                        'БЕЗЛИМИТНЫЙ ИИ-помощник Edusfera',
                        'Авто-чеки НПД на автопилоте',
                        'ТОП-1 размещение в каталоге репетиторов',
                        '100 ГБ облака + запись уроков в HD',
                        'Мгновенный вывод средств (0% комиссии)',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Загрузка фото репетитора.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // Max 5MB
        ], [
            'photo.required' => 'Пожалуйста, выберите файл изображения.',
            'photo.image' => 'Загруженный файл должен быть изображением.',
            'photo.mimes' => 'Поддерживаются форматы JPG, PNG и WEBP.',
            'photo.max' => 'Размер файла не должен превышать 5 МБ.',
        ]);

        $file = $request->file('photo');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки файла.'], 422);
        }

        $filename = 'tutor_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs('avatars', $filename, 'public');

        $profile = TutorProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $profile->updateQuietly([
            'avatar_path' => $storedPath,
        ]);

        $user->updateQuietly([
            'avatar' => $storedPath,
        ]);

        return response()->json([
            'success' => true,
            'avatar_url' => $profile->getAvatarUrl(),
            'message' => 'Фото профиля успешно обновлено!',
        ]);
    }

    /**
     * Сохранение Шага 1: Предметы, ставка, визитка и специализация.
     */
    public function saveStep1(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['string', 'max:100'],
            'price_per_hour' => ['nullable', 'numeric', 'min:10', 'max:1000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'exam_specializations' => ['nullable', 'array'],
            'exam_specializations.*' => ['string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['nullable', 'boolean'],
        ], [
            'price_per_hour.min' => 'Минимальная ставка — 10 BYN за час.',
        ]);

        if (! empty($validated['name'])) {
            $user->updateQuietly([
                'name' => trim($validated['name']),
            ]);
        }

        $profile = TutorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['is_public' => false]
        );

        $updateData = [];
        if (array_key_exists('is_public', $validated)) {
            $updateData['is_public'] = (bool) $validated['is_public'];
        } elseif ($profile->wasRecentlyCreated) {
            $updateData['is_public'] = false;
        }
        if (! empty($validated['subjects'])) {
            $updateData['subjects'] = array_values(array_unique($validated['subjects']));
        } elseif (empty($profile->subjects)) {
            $updateData['subjects'] = ['Математика'];
        }

        if (isset($validated['price_per_hour']) && (float) $validated['price_per_hour'] >= 10) {
            $updateData['price_per_hour'] = (float) $validated['price_per_hour'];
        } elseif (! $profile->price_per_hour) {
            $updateData['price_per_hour'] = 45.0;
        }

        if (isset($validated['experience_years'])) {
            $updateData['experience_years'] = (int) $validated['experience_years'];
        }
        if (isset($validated['exam_specializations'])) {
            $updateData['exam_specializations'] = array_values(array_unique($validated['exam_specializations']));
        }
        if (isset($validated['bio'])) {
            $updateData['bio'] = trim((string) $validated['bio']);
        }

        $profile->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Данные анкеты успешно сохранены.',
        ]);
    }

    /**
     * Сохранение Шага 2: График занятости репетитора.
     */
    public function saveStep2(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $validated = $request->validate([
            'availability_days' => ['required', 'array', 'min:1'],
            'availability_days.*' => ['integer', 'between:0,6'],
            'start_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'end_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ], [
            'availability_days.required' => 'Выберите хотя бы один рабочий день.',
            'availability_days.min' => 'Выберите хотя бы один рабочий день.',
            'start_time.required' => 'Укажите время начала занятий.',
            'end_time.required' => 'Укажите время окончания занятий.',
        ]);

        if ($validated['start_time'] >= $validated['end_time']) {
            return response()->json([
                'success' => false,
                'message' => 'Время окончания должно быть позже времени начала.',
            ], 422);
        }

        // Recreate availability records for all 7 days
        TutorAvailability::query()->where('user_id', $user->id)->delete();

        $allDays = [1, 2, 3, 4, 5, 6, 0];
        foreach ($allDays as $day) {
            $isActive = in_array($day, $validated['availability_days'], true);
            TutorAvailability::query()->create([
                'user_id' => $user->id,
                'day_of_week' => $day,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_active' => $isActive,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'График занятости успешно сохранён.',
        ]);
    }

    /**
     * Генерация самопрезентации с помощью ИИ.
     */
    public function generateBio(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $subjects = $request->input('subjects', []);
        $specs = $request->input('specializations', []);

        $subjectStr = ! empty($subjects) ? implode(', ', $subjects) : 'школьным предметам';
        $specsStr = ! empty($specs) ? implode(', ', $specs) : 'ЦТ/ЦЭ 2026';
        $name = $user->name ?: 'Преподаватель';

        $prompt = "Напиши краткую, доброжелательную презентацию репетитора для карточки на сайте (1-2 емких предложения).\n".
            "Имя репетитора: {$name}.\n".
            "Предмет: {$subjectStr}.\n".
            "Направление: {$specsStr}.\n".
            "ВАЖНО:\n".
            "1. Выведи ТОЛЬКО готовый текст презентации для карточки.\n".
            "2. Никаких звездочек, сносок, кавычек или примечаний.\n".
            "3. СТРОЖАЙШЕ ЗАПРЕЩЕНО писать внизу подсчет слов. Никаких метаданных.";

        try {
            $generated = app(GeminiService::class)->generateText($prompt, 'Ты ведущий методист образовательной платформы Edusfera.by.');
            $clean = $this->cleanBioText((string) $generated);
            if (empty($clean)) {
                $clean = "Понятно и спокойно объясняю {$subjectStr}, готовлю к {$specsStr} на максимальный результат. Индивидуальный подход и поддержка на каждом занятии.";
            }
        } catch (\Throwable $e) {
            $clean = "Понятно и спокойно объясняю {$subjectStr}, готовлю к {$specsStr} на максимальный результат. Индивидуальный подход и поддержка на каждом занятии.";
        }

        return response()->json([
            'success' => true,
            'bio' => $clean,
        ]);
    }

    /**
     * Проверка промокода перед активацией.
     */
    public function checkPromo(Request $request, PromoCodeService $service): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $code = trim((string) $request->input('code', ''));
        if (empty($code)) {
            return response()->json(['success' => false, 'message' => 'Пожалуйста, укажите промокод.'], 422);
        }

        $promo = $service->findByCode($code);
        if (! $promo || ! $promo->is_active) {
            return response()->json(['success' => false, 'message' => 'Промокод не найден или отключен.'], 404);
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Срок действия промокода ещё не наступил.'], 422);
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Срок действия промокода истёк.'], 422);
        }

        if ($promo->max_uses !== null && $promo->used_count >= $promo->max_uses) {
            return response()->json(['success' => false, 'message' => 'Лимит активаций этого промокода исчерпан.'], 422);
        }

        $isLifetime = $promo->isLifetimeSubscription();
        $isFree = $isLifetime || $promo->isFreeSubscription() || $promo->discount_type === 'free_period';
        $months = $promo->getSubscriptionMonths();

        $planTarget = ($promo->subscription_plan && $promo->subscription_plan !== 'any')
            ? $promo->subscription_plan
            : ($request->input('plan') ?: 'pro');

        $periodStr = $isLifetime
            ? 'бессрочный доступ'
            : match ($promo->subscription_period) {
                '3_months' => '3 месяца',
                '6_months' => '6 месяцев',
                '12_months' => '1 год',
                default => $months === 1 ? '1 месяц' : "{$months} мес.",
            };

        $message = $isFree
            ? "Промокод {$promo->code} предоставляет бесплатный доступ на {$periodStr}!"
            : "Промокод {$promo->code} даёт скидку {$promo->discount_value}% на {$months} мес.";

        return response()->json([
            'success' => true,
            'code' => $promo->code,
            'is_free' => $isFree,
            'is_lifetime' => $isLifetime,
            'months' => $months,
            'plan' => $planTarget,
            'period_text' => $periodStr,
            'message' => $message,
        ]);
    }

    /**
     * Завершение анкеты: выбор тарифа / активация промокода,
     * отправка анкеты на модерацию администратору и открытие доступа в кабинет.
     */
    public function completeOnboarding(Request $request, PromoCodeService $promoService): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $user->isTutor()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован как репетитор.'], 403);
        }

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:start,pro,premium'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'is_yearly' => ['nullable', 'boolean'],
        ]);

        $profile = TutorProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $planEnum = SubscriptionPlan::tryFrom($validated['plan']) ?? SubscriptionPlan::PRO;
        $promoInput = trim((string) ($validated['promo_code'] ?? ''));

        $activatedViaPromo = false;
        // If promo code provided, try applying it
        if (! empty($promoInput)) {
            $promo = $promoService->findByCode($promoInput);
            if ($promo && $promo->is_active) {
                try {
                    $promoResult = $promoService->activateFreeSubscriptionViaPromo($promo, $user, $planEnum->value);
                    $subscription = $promoResult['subscription'];
                    $activatedViaPromo = true;
                } catch (\Throwable $e) {
                    Log::warning('Promo code failed in onboarding complete: '.$e->getMessage());
                    // Fall back to trial below
                }
            }
        }

        // If not activated via free promo, subscription remains EXPIRED (requires payment or promo)
        if (! $activatedViaPromo) {
            $subscription = Subscription::where('tutor_id', $user->id)->first();
            $now = now();
            if (! $subscription) {
                $subscription = Subscription::create([
                    'tutor_id' => $user->id,
                    'plan' => $planEnum,
                    'status' => SubscriptionStatus::EXPIRED,
                    'is_founder' => false,
                    'trial_ends_at' => null,
                    'current_period_starts_at' => $now,
                    'current_period_ends_at' => $now,
                    'grace_period_ends_at' => null,
                    'responses_used_this_month' => 0,
                    'is_onboarded' => true,
                ]);
            } else {
                $subscription->update([
                    'plan' => $planEnum,
                    'is_onboarded' => true,
                ]);
            }
        }

        if (isset($subscription)) {
            $subscription->update(['is_onboarded' => true]);
        }

        // Submit profile for administrator review & moderation
        app(TutorVerificationService::class)->submitForReview($profile);

        return response()->json([
            'success' => true,
            'redirect' => $activatedViaPromo
                ? ($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin')
                : ($this->isMobileDevice($request) ? route('mobile.cabinet') : '/admin/tutor-subscription-page'),
            'message' => $activatedViaPromo
                ? 'Анкета успешно отправлена на модерацию! Доступ в личный кабинет открыт.'
                : 'Анкета заполнена! Выберите тариф или введите промокод для доступа к личному кабинету.',
        ]);
    }

    private function cleanBioText(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $validLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

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
        $joined = preg_replace('/\*{1,3}(.*?)\*{1,3}/u', '$1', $joined);
        $joined = str_replace('*', '', $joined);
        $joined = preg_replace('/[\(\[\*]\s*~?\s*\d+\s*(?:слов|слова|слово|знаков)\s*[\)\]\*]/iu', '', $joined);
        $joined = str_replace(['"', '«', '»', '`'], '', $joined);

        return trim(preg_replace('/\s+/', ' ', $joined));
    }

    /**
     * Запрос звонка «Настройка за 5 минут».
     */
    public function requestHelp(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $phone = $validated['phone'] ?? null;
        if (! $phone && ! $user->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Пожалуйста, укажите контактный номер телефона для связи.',
            ], 422);
        }

        /** @var \App\Services\HelpCallbackService $helpService */
        $helpService = app(\App\Services\HelpCallbackService::class);
        $result = $helpService->requestCallback($user, $phone);

        return response()->json($result);
    }
}

