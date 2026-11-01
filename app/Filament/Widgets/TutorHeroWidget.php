<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Models\Lesson;
use Filament\Widgets\Widget;

/**
 * Верхний блок кабинета репетитора: приветствие, ближайший урок с живым
 * обратным отсчётчиком и единственная главная кнопка экрана (вход в класс).
 */
class TutorHeroWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-hero-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    private const JOIN_LEAD_MINUTES = 15;

    public static function canView(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $timezone = config('booking.display_timezone', 'Europe/Minsk');

        $hour = (int) now($timezone)->format('G');
        $greeting = match (true) {
            $hour < 6 => 'Доброй ночи',
            $hour < 12 => 'Доброе утро',
            $hour < 18 => 'Добрый день',
            default => 'Добрый вечер',
        };

        $now = now()->utc();

        // Ближайший урок: подтверждённый и ещё не закончившийся
        // (включая идущий прямо сейчас).
        $upcomingLesson = Lesson::query()
            ->with(['student', 'parent', 'conversation'])
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_CONFIRMED)
            ->where('end_time', '>', $now)
            ->orderBy('start_time')
            ->first();

        $meetingJoinAvailable = $upcomingLesson !== null
            && $now->greaterThanOrEqualTo($upcomingLesson->start_time->copy()->subMinutes(self::JOIN_LEAD_MINUTES));

        $classroomUrl = null;
        $studentChatUrl = '/admin/messages';
        $studentName = 'Ученик';
        $studentInitial = 'У';
        $studentAvatarUrl = null;
        $lessonSubject = 'Индивидуальное занятие';
        $isLessonPaid = false;

        if ($upcomingLesson) {
            $classroomUrl = ! empty($upcomingLesson->meeting_link) && str_starts_with($upcomingLesson->meeting_link, 'http')
                ? $upcomingLesson->meeting_link
                : route('classroom.show', $upcomingLesson);

            if ($upcomingLesson->conversation_id) {
                $studentChatUrl = '/admin/messages?conversation='.$upcomingLesson->conversation_id;
            }

            $studentUser = $upcomingLesson->student ?? $upcomingLesson->parent;
            if ($studentUser) {
                $studentName = $studentUser->name ?? 'Ученик';
                $nameParts = preg_split('/\s+/', trim((string) $studentName));
                $studentInitial = count($nameParts) >= 2
                    ? mb_strtoupper(mb_substr($nameParts[0], 0, 1).mb_substr($nameParts[1], 0, 1))
                    : mb_strtoupper(mb_substr((string) $studentName, 0, 1));
                $studentAvatarUrl = $studentUser->avatar_url ?? null;
            }

            $lessonSubject = $upcomingLesson->subject ?? $upcomingLesson->package_label ?? 'Индивидуальное занятие';
            $isLessonPaid = ($upcomingLesson->payment_status === Lesson::PAYMENT_PAID);
        }

        $newRequestsCount = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->count();

        // Подписка репетитора
        $subscription = $user->subscription;
        if (! $subscription) {
            /** @var SubscriptionService $subService */
            $subService = app(SubscriptionService::class);
            $subscription = $subService->startTrial($user, SubscriptionPlan::PRO);
        }

        $isTrial = $subscription->status === SubscriptionStatus::TRIAL;
        $isActive = $subscription->status === SubscriptionStatus::ACTIVE;
        $isInGrace = $subscription->isInGracePeriod();
        $daysRemaining = $subscription->daysRemaining();
        $graceDaysRemaining = $subscription->graceDaysRemaining();
        $isExpiringSoon = ($isTrial || $isActive) && $daysRemaining <= 3 && $daysRemaining > 0;

        if ($isTrial) {
            $subPillText = "Пробный период: {$daysRemaining} ".trans_choice('день|дня|дней', $daysRemaining).' бесплатно';
        } elseif ($isActive) {
            $subPillText = 'Тариф: '.$subscription->plan->title();
        } elseif ($isInGrace) {
            $subPillText = 'Льготный период ('.$graceDaysRemaining.' '.trans_choice('день|дня|дней', $graceDaysRemaining).')';
        } else {
            $subPillText = 'Тариф: '.$subscription->plan->title();
        }

        // Персональная ссылка для записи
        $tutorProfile = $user->tutorProfile;
        $bookingUrl = $tutorProfile
            ? route('tutors.show', $tutorProfile)
            : url('/tutors/'.$user->id);

        return [
            'greeting' => $greeting,
            'firstName' => explode(' ', trim((string) $user->name))[0] ?? $user->name,
            'tutorFullName' => (string) $user->name,
            'dateLine' => now($timezone)->translatedFormat('l, j F'),
            'upcomingLesson' => $upcomingLesson,
            'studentName' => $studentName,
            'studentInitial' => $studentInitial,
            'studentAvatarUrl' => $studentAvatarUrl,
            'studentChatUrl' => $studentChatUrl,
            'lessonSubject' => $lessonSubject,
            'isLessonPaid' => $isLessonPaid,
            'lessonStartsAt' => $upcomingLesson?->start_time->getTimestamp(),
            'lessonEndsAt' => $upcomingLesson?->end_time->getTimestamp(),
            'lessonJoinFrom' => $upcomingLesson?->start_time->copy()->subMinutes(self::JOIN_LEAD_MINUTES)->getTimestamp(),
            'lessonJoinUntil' => $upcomingLesson?->end_time->getTimestamp(),
            'meetingJoinAvailable' => $meetingJoinAvailable,
            'classroomUrl' => $classroomUrl,
            'newRequestsCount' => $newRequestsCount,
            'subscription' => $subscription,
            'subPillText' => $subPillText,
            'isTrial' => $isTrial,
            'isActive' => $isActive,
            'isInGrace' => $isInGrace,
            'isExpiringSoon' => $isExpiringSoon,
            'daysRemaining' => $daysRemaining,
            'graceDaysRemaining' => $graceDaysRemaining,
            'bookingUrl' => $bookingUrl,
            'isPublic' => (bool) ($tutorProfile?->is_public ?? false),
            'isCouncilExpert' => (bool) ($tutorProfile?->is_council_expert ?? false),
            'councilNumber' => $tutorProfile?->council_number,
            'councilCertNumber' => $tutorProfile?->council_certificate_number ?? ('МС-2026/'.str_pad((string) ($tutorProfile?->council_number ?: 1), 2, '0', STR_PAD_LEFT)),
            'councilJoinedAt' => $tutorProfile?->council_joined_at,
            'quotaTotal' => \App\Models\TutorProfile::councilQuotaTotal(),
            'quotaLeft' => \App\Models\TutorProfile::councilQuotaLeft(),
        ];
    }

    public function joinCouncil(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isTutor()) {
            return;
        }

        $profile = $user->tutorProfile ?: \App\Models\TutorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['is_public' => false]
        );

        if ($profile->isCouncilExpert()) {
            return;
        }

        $profile->joinCouncil();

        // Предоставляем 30 дней тарифа PRO бесплатно
        $now = \Carbon\CarbonImmutable::now();
        $sub = $user->subscription;
        if ($sub) {
            $sub->update([
                'plan' => SubscriptionPlan::PRO,
                'status' => SubscriptionStatus::ACTIVE,
                'trial_ends_at' => $now->addDays(30),
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $now->addDays(30),
                'grace_period_ends_at' => null,
            ]);
        } else {
            \App\Domain\Subscription\Models\Subscription::create([
                'tutor_id' => $user->id,
                'plan' => SubscriptionPlan::PRO,
                'status' => SubscriptionStatus::ACTIVE,
                'is_founder' => true,
                'trial_ends_at' => $now->addDays(30),
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $now->addDays(30),
                'grace_period_ends_at' => null,
                'responses_used_this_month' => 0,
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Добро пожаловать в Общественный методический совет!')
            ->body("Вам присвоен бессрочный статус Эксперта и выдано Свидетельство {$profile->council_certificate_number}. Тариф PRO активирован на 30 дней бесплатно.")
            ->success()
            ->duration(10000)
            ->send();
    }

    public function toggleProfileVisibility(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isTutor()) {
            return;
        }

        $profile = $user->tutorProfile ?: TutorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['is_public' => false]
        );

        $targetPublic = ! (bool) $profile->is_public;

        if (! $targetPublic && ! app(\App\Domain\Subscription\Services\SubscriptionFeatureGate::class)->canHideProfile($user)) {
            \Filament\Notifications\Notification::make()
                ->title('Приватный режим требует тариф PRO или Онлайн-школа')
                ->body('Скрытие анкеты из каталога доступно на тарифах от 40 BYN/мес или в течение 14-дневного бесплатного пробного периода.')
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('upgrade')
                        ->label('Выбрать тариф')
                        ->url('/admin/tutor-subscription-page'),
                ])
                ->send();

            return;
        }

        $profile->is_public = $targetPublic;
        $profile->save();

        \Filament\Notifications\Notification::make()
            ->title($profile->is_public ? 'Анкета опубликована в каталоге' : 'Кабинет переведён в приватный режим')
            ->body($profile->is_public ? 'Ученики и поисковики теперь видят вашу анкету в каталоге Edusfera.' : 'Ваша анкета и цены скрыты от посторонних. Доступ только по вашей личной ссылке.')
            ->success()
            ->send();
    }
}
