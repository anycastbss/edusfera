<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\StudentGoal;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Models\WalletTopup;
use App\Notifications\LessonCancelledNotification;
use App\Notifications\LessonConfirmedNotification;
use App\Services\Ai\GeminiService;
use App\Services\HomeworkService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\PromoCodeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileCabinetController extends Controller
{
    use \App\Traits\DetectsMobileDevice;

    /**
     * Render the Mobile Cabinet SPA shell.
     */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($this->isDesktopDevice($request)) {
            if ($request->hasSession()) {
                $request->session()->forget('prefer_mobile');
            }
            cookie()->queue('edusfera_device', 'desktop', 60 * 24 * 365, '/', null, null, false, false, 'Lax');

            $tab = $request->query('tab');
            $query = $tab ? '?tab=' . $tab : '';

            return redirect('/cabinet' . $query);
        }

        if ($request->hasSession()) {
            $request->session()->forget('prefer_desktop');
            $request->session()->put('prefer_mobile', true);
        }

        cookie()->queue('edusfera_device', 'mobile', 60 * 24 * 365, '/', null, null, false, false, 'Lax');

        $initialData = $this->buildInitialCabinetData($request);
        $initialData['is_desktop'] = false;

        return view('mobile.cabinet', [
            'initialData' => $initialData,
        ]);
    }

    /**
     * Render the Unified Cabinet SPA shell (/cabinet) for Desktop and Mobile.
     */
    public function indexCabinet(Request $request): View
    {
        $isDesktop = $this->isDesktopDevice($request);
        $initialData = $this->buildInitialCabinetData($request);
        $initialData['is_desktop'] = $isDesktop;

        return view('cabinet.index', [
            'initialData' => $initialData,
        ]);
    }

    /**
     * Build unified dataset for Cabinet SPA hydration.
     *
     * @return array<string, mixed>
     */
    protected function buildInitialCabinetData(Request $request): array
    {
        /** @var User $user */
        $user = Auth::user();
        $isTutor = $user->isTutor();
        $isStudent = $user->isStudent();
        $isParent = $user->isParent();
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $profile = $isTutor 
            ? TutorProfile::where('user_id', $user->id)->first() 
            : null;

        $subscription = $isTutor 
            ? Subscription::where('tutor_id', $user->id)->where('status', 'active')->first() 
            : null;

        // Financial balances
        $balance = null;
        if ($isTutor) {
            $tutorBal = $user->tutorBalance;
            $balance = [
                'available' => (float) ($tutorBal?->available_amount ?? 0.0),
                'pending' => (float) ($tutorBal?->pending_amount ?? 0.0),
                'currency' => 'BYN',
            ];
        } else {
            $studentBal = $user->studentBalance;
            $balance = [
                'available' => (float) ($studentBal?->balance_amount ?? 0.0),
                'pending' => 0.0,
                'total_spent' => (float) ($studentBal?->total_spent ?? 0.0),
                'currency' => 'BYN',
            ];
        }

        // Compute real statistics for tutor
        $completedLessons = $isTutor 
            ? Lesson::where('tutor_id', $user->id)->where('status', 'completed')->count() 
            : Lesson::where(function ($q) use ($user) { $q->where('student_id', $user->id)->orWhere('parent_id', $user->id); })->where('status', 'completed')->count();

        $completedHours = $isTutor 
            ? round((float) Lesson::where('tutor_id', $user->id)->where('status', 'completed')->sum('duration_minutes') / 60, 1) 
            : round((float) Lesson::where(function ($q) use ($user) { $q->where('student_id', $user->id)->orWhere('parent_id', $user->id); })->where('status', 'completed')->sum('duration_minutes') / 60, 1);

        $thisMonthLessons = $isTutor 
            ? Lesson::where('tutor_id', $user->id)->where('status', 'completed')->whereMonth('start_time', now()->month)->whereYear('start_time', now()->year)->count() 
            : Lesson::where(function ($q) use ($user) { $q->where('student_id', $user->id)->orWhere('parent_id', $user->id); })->where('status', 'completed')->whereMonth('start_time', now()->month)->whereYear('start_time', now()->year)->count();

        $thisMonthEarnings = $isTutor 
            ? (float) Lesson::where('tutor_id', $user->id)->where('status', 'completed')->where('payment_status', 'paid')->whereMonth('start_time', now()->month)->whereYear('start_time', now()->year)->sum('price') 
            : 0.0;

        $lastMonthEarnings = $isTutor 
            ? (float) Lesson::where('tutor_id', $user->id)->where('status', 'completed')->where('payment_status', 'paid')->whereMonth('start_time', now()->subMonth()->month)->whereYear('start_time', now()->subMonth()->year)->sum('price') 
            : 0.0;

        $allTimeEarnings = $isTutor 
            ? (float) Lesson::where('tutor_id', $user->id)->where('payment_status', 'paid')->sum('price') 
            : 0.0;

        $activeStudentsCount = $isTutor 
            ? Lesson::where('tutor_id', $user->id)->distinct('student_id')->count('student_id') 
            : 0;

        // Notifications
        $notifications = $user->notifications()->latest()->limit(30)->get()->map(function ($n) {
            $data = is_array($n->data) ? $n->data : [];
            $title = $data['title'] ?? 'Уведомление';
            $message = $data['message'] ?? $data['body'] ?? $data['text'] ?? 'Событие в личном кабинете';
            $type = 'system';
            $baseName = class_basename($n->type);
            if (str_contains($baseName, 'Lesson')) $type = 'lesson';
            elseif (str_contains($baseName, 'Payment') || str_contains($baseName, 'Payout')) $type = 'finance';
            elseif (str_contains($baseName, 'Chat') || str_contains($baseName, 'Message')) $type = 'chat';
            elseif (str_contains($baseName, 'Subscription')) $type = 'subscription';

            return [
                'id' => (string) $n->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->diffForHumans() ?? 'только что',
                'timestamp' => $n->created_at?->format('d.m.Y H:i') ?? '',
            ];
        })->values()->all();

        $unreadNotificationsCount = $user->unreadNotifications()->count();

        // Direct booking URLs
        $bookingUrl = $profile ? url('/tutors/' . $profile->id) : url('/tutors');
        $bookingShortUrl = $profile ? ('edusfera.by/tutors/' . $profile->id) : 'edusfera.by/tutors';

        // Tutor specific datasets
        $npdData = $isTutor ? $this->computeNpdData($user) : null;
        $tutorStudents = [];
        $availabilityData = [];
        $pendingRequests = [];
        if ($isTutor) {
            $studentIds = Lesson::where('tutor_id', $user->id)
                ->whereNotNull('student_id')
                ->pluck('student_id')
                ->unique();
            $tutorStudents = User::whereIn('id', $studentIds)->select('id', 'name', 'email', 'phone')->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone ?? '',
                    'email' => $s->email,
                    'avatar_url' => $s->getFilamentAvatarUrl(),
                ];
            })->values()->all();

            $existingAvail = \App\Models\TutorAvailability::where('user_id', $user->id)
                ->get()
                ->keyBy('day_of_week');

            $availabilityData = collect([
                1 => 'Понедельник',
                2 => 'Вторник',
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота',
                0 => 'Воскресенье',
            ])->map(function (string $label, int $day) use ($existingAvail) {
                $row = $existingAvail->get($day);
                return [
                    'day_of_week' => $day,
                    'day_label' => $label,
                    'is_active' => (bool) ($row?->is_active ?? false),
                    'start_time' => substr((string) ($row?->start_time ?? '10:00'), 0, 5),
                    'end_time' => substr((string) ($row?->end_time ?? '18:00'), 0, 5),
                ];
            })->values()->all();

            $pendingRequests = Lesson::query()
                ->with(['student', 'parent'])
                ->where('tutor_id', $user->id)
                ->where('status', Lesson::STATUS_PENDING)
                ->orderBy('start_time')
                ->limit(10)
                ->get()
                ->map(function (Lesson $req) use ($displayTz) {
                    $start = $req->start_time ? Carbon::parse($req->start_time)->setTimezone($displayTz) : null;
                    return [
                        'id' => $req->id,
                        'student_name' => $req->student?->name ?? 'Новый ученик',
                        'student_id' => $req->student_id,
                        'student_avatar' => $req->student?->getFilamentAvatarUrl(),
                        'student_phone' => $req->student?->phone ?? '',
                        'subject' => $req->subject ?: 'Индивидуальное занятие',
                        'price' => (float) $req->price,
                        'date_formatted' => $start ? $start->translatedFormat('d F (D)') : '',
                        'time_formatted' => $start ? $start->format('H:i') : '',
                        'duration_minutes' => $req->duration_minutes ?: 60,
                        'notes' => $req->notes,
                    ];
                })->values()->all();
        }

        // Student / Parent specific datasets
        $studentHomework = [];
        $myTutors = [];
        $studentGoal = null;
        if ($isStudent || $isParent) {
            $studentHomework = HomeworkAssignment::query()
                ->with(['lesson.tutor', 'studentGoal'])
                ->where('student_id', $user->id)
                ->latest('assigned_at')
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(function ($hw) {
                    return [
                        'id' => $hw->id,
                        'title' => $hw->title,
                        'instructions' => $hw->instructions,
                        'status' => $hw->status,
                        'assigned_at' => $hw->assigned_at?->format('d.m.Y'),
                        'due_at' => $hw->due_at?->format('d.m.Y'),
                        'is_overdue' => $hw->due_at ? ($hw->due_at->isPast() && $hw->status !== 'completed') : false,
                        'completed_at' => $hw->completed_at?->format('d.m.Y H:i'),
                        'tutor_name' => $hw->tutor?->name ?? 'Преподаватель',
                        'tutor_avatar' => $hw->tutor?->getFilamentAvatarUrl(),
                        'subject' => $hw->lesson?->subject ?? 'Занятие',
                    ];
                })->values()->all();

            $tutorIds = Lesson::where('student_id', $user->id)->pluck('tutor_id')->unique()->filter();
            $myTutors = User::whereIn('id', $tutorIds)->with('tutorProfile')->get()->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'avatar_url' => $t->getFilamentAvatarUrl(),
                    'subject' => $t->tutorProfile?->subject ?? 'Репетитор',
                    'hourly_rate' => (float) ($t->tutorProfile?->price_per_hour ?? 35.0),
                    'rating' => (float) ($t->tutorProfile?->rating_avg ?? 5.0),
                    'booking_url' => url('/tutors/' . ($t->tutorProfile?->id ?? $t->id)),
                ];
            })->values()->all();

            $goal = StudentGoal::where('student_id', $user->id)->latest('id')->first();
            if ($goal) {
                $studentGoal = [
                    'id' => $goal->id,
                    'subject' => $goal->subject,
                    'exam_type' => $goal->exam_type,
                    'target_score' => $goal->target_score,
                    'current_score' => $goal->current_score,
                ];
            }
        }

        $initialData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'role' => $user->role->value ?? (string) $user->role,
                'is_admin' => $user->isAdmin(),
                'is_tutor' => $isTutor,
                'is_student' => $isStudent,
                'is_parent' => $isParent,
                'avatar_url' => $user->getFilamentAvatarUrl() ?: ($profile?->getAvatarUrl() ?? null),
                'initials' => $this->getInitials($user->name),
            ],
            'tutor_profile' => $profile ? [
                'id' => $profile->id,
                'headline' => $profile->headline ?? $profile->bio ?? 'Репетитор Edusfera',
                'bio' => $profile->bio ?? '',
                'education_summary' => $profile->education_summary ?? '',
                'teaching_methodology' => $profile->teaching_methodology ?? '',
                'achievements' => $profile->achievements ?? '',
                'experience_years' => (int) ($profile->experience_years ?? 0),
                'legal_status' => $profile->legal_status ?? 'none',
                'audiences' => is_array($profile->audiences) ? $profile->audiences : [],
                'hourly_rate' => (float) ($profile->price_per_hour ?? 35.0),
                'is_active' => (bool) $profile->is_public,
                'is_approved' => (bool) ($profile->is_verified ?? false),
                'is_verified' => (bool) ($profile->is_verified ?? false),
                'verification_status' => $profile->verification_status ?? ($profile->is_verified ? 'approved' : ($profile->diploma_path ? 'pending' : 'unverified')),
                'diploma_path' => $profile->diploma_path ? asset('storage/' . $profile->diploma_path) : null,
                'diploma_filename' => $profile->diploma_path ? basename($profile->diploma_path) : null,
                'rating' => (float) ($profile->rating_avg ?? 5.0),
                'reviews_count' => (int) ($profile->reviews_count ?? 0),
                'subjects' => is_array($profile->subjects) ? $profile->subjects : [],
                'lesson_formats' => is_array($profile->lesson_formats) ? $profile->lesson_formats : ['online'],
                'comfort_vision_mode' => (bool) ($profile->comfort_vision_mode ?? false),
                'is_council_expert' => (bool) ($profile->is_council_expert ?? false),
                'council_number' => $profile->council_number,
                'council_certificate_number' => $profile->council_certificate_number,
                'telegram_username' => $profile->telegram_username ?? $user->telegram_username,
                'booking_url' => $bookingUrl,
                'booking_short_url' => $bookingShortUrl,
            ] : null,
            'subscription' => $subscription ? [
                'plan' => $subscription->plan->value ?? (string) $subscription->plan,
                'plan_name' => $subscription->plan instanceof \App\Domain\Subscription\Enums\SubscriptionPlan ? $subscription->plan->label() : strtoupper((string) $subscription->plan),
                'is_active' => $subscription->isActive(),
                'status' => $subscription->status,
                'period_end' => $subscription->current_period_end?->format('d.m.Y'),
                'days_left' => $subscription->current_period_end ? max(0, (int) now()->diffInDays($subscription->current_period_end, false)) : 0,
                'ai_used' => (int) ($subscription->ai_generations_used_this_month ?? 0),
                'ai_limit' => ($subscription->plan instanceof \App\Domain\Subscription\Enums\SubscriptionPlan ? $subscription->plan->aiMonthlyLimit() : 10) ?? 999,
                'responses_used' => (int) ($subscription->responses_used_this_period ?? 0),
                'responses_limit' => ($subscription->plan instanceof \App\Domain\Subscription\Enums\SubscriptionPlan ? $subscription->plan->maxResponsesPerMonth() : 20) ?? 20,
            ] : [
                'plan' => 'free',
                'plan_name' => 'Базовый',
                'is_active' => false,
                'status' => 'inactive',
                'period_end' => 'Не оформлена',
                'days_left' => 0,
                'ai_used' => 0,
                'ai_limit' => 5,
                'responses_used' => 0,
                'responses_limit' => 3,
            ],
            'subscription_plans' => [
                [
                    'id' => 'start',
                    'name' => 'Стандарт',
                    'badge' => 'Для старта',
                    'price_monthly' => 20,
                    'price_yearly_monthly' => 16,
                    'price_yearly_total' => 192,
                    'trial_days' => 5,
                    'is_current' => in_array($subscription?->plan, [\App\Domain\Subscription\Enums\SubscriptionPlan::START, \App\Domain\Subscription\Enums\SubscriptionPlan::BASIC], true),
                    'features' => \App\Domain\Subscription\Enums\SubscriptionPlan::START->features(),
                ],
                [
                    'id' => 'pro',
                    'name' => 'Pro',
                    'badge' => '⭐ Хит продаж',
                    'price_monthly' => 40,
                    'price_yearly_monthly' => 32,
                    'price_yearly_total' => 384,
                    'trial_days' => 14,
                    'is_current' => $subscription?->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PRO,
                    'features' => \App\Domain\Subscription\Enums\SubscriptionPlan::PRO->features(),
                ],
                [
                    'id' => 'premium',
                    'name' => 'Премиум',
                    'badge' => '👑 Топ-эксперт',
                    'price_monthly' => 60,
                    'price_yearly_monthly' => 48,
                    'price_yearly_total' => 576,
                    'trial_days' => 28,
                    'is_current' => $subscription?->plan === \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM,
                    'features' => \App\Domain\Subscription\Enums\SubscriptionPlan::PREMIUM->features(),
                ],
            ],
            'balance' => $balance,
            'stats' => [
                'completed_lessons' => $completedLessons,
                'completed_hours' => $completedHours,
                'this_month_lessons' => $thisMonthLessons,
                'this_month_earnings' => $thisMonthEarnings,
                'last_month_earnings' => $lastMonthEarnings,
                'all_time_earnings' => $allTimeEarnings,
                'active_students' => $activeStudentsCount,
                'average_rate' => (float) ($profile?->price_per_hour ?? 35.0),
            ],
            'npd' => $npdData,
            'tutor_students' => $tutorStudents,
            'availability' => $availabilityData,
            'pending_requests' => $pendingRequests,
            'student_homework' => $studentHomework,
            'my_tutors' => $myTutors,
            'student_goal' => $studentGoal,
            'notifications' => $notifications,
            'unread_notifications_count' => $unreadNotificationsCount,
            'booking_url' => $bookingUrl,
            'booking_short_url' => $bookingShortUrl,
        ];

        return $initialData;
    }

    /**
     * API: Get lessons for specified week or date.
     */
    public function getLessons(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isTutor = $user->isTutor();

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $weekStartParam = $request->query('week_start');

        $weekStart = $weekStartParam 
            ? Carbon::parse($weekStartParam, $displayTz)->startOfWeek() 
            : Carbon::now($displayTz)->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $query = Lesson::query()
            ->with(['tutor.tutorProfile', 'student', 'parent'])
            ->where(function ($q) use ($user, $isTutor) {
                if ($isTutor) {
                    $q->where('tutor_id', $user->id);
                } else {
                    $q->where('student_id', $user->id);
                }
            })
            ->where('start_time', '>=', $weekStart->copy()->startOfDay()->setTimezone('UTC'))
            ->where('start_time', '<=', $weekEnd->copy()->endOfDay()->setTimezone('UTC'))
            ->whereNotIn('status', [Lesson::STATUS_CANCELLED])
            ->orderBy('start_time');

        $lessons = $query->get()->map(function (Lesson $lesson) use ($displayTz, $isTutor) {
            $start = $lesson->start_time ? Carbon::parse($lesson->start_time)->setTimezone($displayTz) : null;
            $end = $lesson->end_time ? Carbon::parse($lesson->end_time)->setTimezone($displayTz) : null;

            $gcalLink = 'https://calendar.google.com/calendar/render?' . http_build_query([
                'action' => 'TEMPLATE',
                'text' => 'Урок: ' . ($isTutor ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор')) . ' (' . ($lesson->subject ?: 'Edusfera') . ')',
                'dates' => $lesson->start_time?->setTimezone('UTC')->format('Ymd\THis\Z') . '/' . $lesson->end_time?->setTimezone('UTC')->format('Ymd\THis\Z'),
                'details' => 'Edusfera онлайн-урок. Ссылка на виртуальный класс: ' . route('classroom.show', $lesson),
                'location' => route('classroom.show', $lesson),
            ]);

            $partnerUser = $isTutor ? $lesson->student : $lesson->tutor;

            return [
                'id' => $lesson->id,
                'title' => $isTutor ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'),
                'subject' => $lesson->subject ?: 'Занятие',
                'partner_name' => $isTutor ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'),
                'partner_id' => $isTutor ? $lesson->student_id : $lesson->tutor_id,
                'partner_avatar' => $partnerUser?->getFilamentAvatarUrl(),
                'partner_initials' => $this->getInitials($partnerUser?->name ?? ($isTutor ? 'Ученик' : 'Репетитор')),
                'status' => $lesson->status,
                'payment_status' => $lesson->payment_status,
                'price' => (float) $lesson->price,
                'start_iso' => $start?->toIso8601String(),
                'end_iso' => $end?->toIso8601String(),
                'date' => $start?->format('Y-m-d'),
                'time_formatted' => $start && $end ? ($start->format('H:i') . ' — ' . $end->format('H:i')) : '',
                'duration_minutes' => $lesson->duration_minutes ?: 60,
                'classroom_url' => route('classroom.show', $lesson),
                'student_invite_url' => $lesson->student_invite_url,
                'checkout_url' => route('checkout.show', $lesson),
                'meeting_link' => $lesson->meeting_link,
                'is_today' => $start?->isToday() ?? false,
                'is_soon' => $start ? ($start->isFuture() && $start->diffInMinutes(now($displayTz)) <= 30) : false,
                'notes' => $lesson->notes,
                'parent_name' => $lesson->parent?->name,
                'gcal_link' => $gcalLink,
                'can_mark_paid' => $isTutor && $lesson->payment_status !== Lesson::PAYMENT_PAID,
                'can_reschedule' => $isTutor && ! in_array($lesson->status, [Lesson::STATUS_CANCELLED, Lesson::STATUS_COMPLETED], true),
                'can_cancel' => $lesson->status !== Lesson::STATUS_CANCELLED,
            ];
        });

        // Find nearest upcoming lesson (starts from 15 mins ago to future)
        $upcomingLesson = Lesson::query()
            ->with(['tutor.tutorProfile', 'student'])
            ->where(function ($q) use ($user, $isTutor) {
                if ($isTutor) {
                    $q->where('tutor_id', $user->id);
                } else {
                    $q->where('student_id', $user->id);
                }
            })
            ->where('start_time', '>=', now()->subMinutes(15))
            ->whereNotIn('status', [Lesson::STATUS_CANCELLED, Lesson::STATUS_COMPLETED])
            ->orderBy('start_time')
            ->first();

        $upcomingPayload = null;
        if ($upcomingLesson) {
            $start = Carbon::parse($upcomingLesson->start_time)->setTimezone($displayTz);
            $end = Carbon::parse($upcomingLesson->end_time)->setTimezone($displayTz);
            $minutesToStart = (int) now($displayTz)->diffInMinutes($start, false);
            $partnerUser = $isTutor ? $upcomingLesson->student : $upcomingLesson->tutor;

            $upcomingPayload = [
                'id' => $upcomingLesson->id,
                'title' => $isTutor ? ($upcomingLesson->student?->name ?? 'Ученик') : ($upcomingLesson->tutor?->name ?? 'Репетитор'),
                'subject' => $upcomingLesson->subject ?: 'Занятие',
                'partner_name' => $isTutor ? ($upcomingLesson->student?->name ?? 'Ученик') : ($upcomingLesson->tutor?->name ?? 'Репетитор'),
                'partner_avatar' => $partnerUser?->getFilamentAvatarUrl(),
                'partner_initials' => $this->getInitials($partnerUser?->name ?? ($isTutor ? 'Ученик' : 'Репетитор')),
                'start_formatted' => $start->format('H:i'),
                'time_span' => $start->format('H:i') . ' — ' . $end->format('H:i'),
                'date_formatted' => $start->isToday() ? 'Сегодня' : ($start->isTomorrow() ? 'Завтра' : $start->translatedFormat('d F')),
                'classroom_url' => route('classroom.show', $upcomingLesson),
                'student_invite_url' => $upcomingLesson->student_invite_url,
                'checkout_url' => route('checkout.show', $upcomingLesson),
                'payment_status' => $upcomingLesson->payment_status,
                'price' => (float) $upcomingLesson->price,
                'is_urgent' => $minutesToStart <= 15 && $minutesToStart >= -60,
                'minutes_to_start' => $minutesToStart,
            ];
        }

        $pendingRequests = [];
        if ($isTutor) {
            $pendingRequests = Lesson::query()
                ->with(['student', 'parent'])
                ->where('tutor_id', $user->id)
                ->where('status', Lesson::STATUS_PENDING)
                ->orderBy('start_time')
                ->limit(10)
                ->get()
                ->map(function (Lesson $req) use ($displayTz) {
                    $start = $req->start_time ? Carbon::parse($req->start_time)->setTimezone($displayTz) : null;
                    return [
                        'id' => $req->id,
                        'student_name' => $req->student?->name ?? 'Новый ученик',
                        'student_id' => $req->student_id,
                        'student_avatar' => $req->student?->getFilamentAvatarUrl(),
                        'student_phone' => $req->student?->phone ?? '',
                        'subject' => $req->subject ?: 'Индивидуальное занятие',
                        'price' => (float) $req->price,
                        'date_formatted' => $start ? $start->translatedFormat('d F (D)') : '',
                        'time_formatted' => $start ? $start->format('H:i') : '',
                        'duration_minutes' => $req->duration_minutes ?: 60,
                        'notes' => $req->notes,
                    ];
                })->values()->all();
        }

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'week_label' => mb_convert_case($weekStart->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8'),
            'week_span_formatted' => $weekStart->format('d.m') . ' — ' . $weekEnd->format('d.m.Y'),
            'lessons' => $lessons,
            'upcoming_lesson' => $upcomingPayload,
            'pending_requests' => $pendingRequests,
            'total_lessons_count' => $lessons->count(),
        ]);
    }

    /**
     * API: Get students for tutor (for scheduling picker).
     */
    public function getStudents(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['students' => []]);
        }

        $studentIds = Lesson::where('tutor_id', $user->id)
            ->pluck('student_id')
            ->unique()
            ->filter();

        $students = User::where('role', UserRole::Student)
            ->where(function ($q) use ($studentIds) {
                if ($studentIds->isNotEmpty()) {
                    $q->whereIn('id', $studentIds);
                }
            })
            ->orderBy('name')
            ->take(30)
            ->get(['id', 'name', 'phone', 'email', 'avatar']);

        if ($students->count() < 5) {
            $moreStudents = User::where('role', UserRole::Student)
                ->whereNotIn('id', $students->pluck('id'))
                ->orderBy('name')
                ->take(15)
                ->get(['id', 'name', 'phone', 'email', 'avatar']);
            $students = $students->merge($moreStudents);
        }

        $formattedStudents = $students->map(function ($st) {
            return [
                'id' => $st->id,
                'name' => $st->name,
                'phone' => $st->phone,
                'email' => $st->email,
                'avatar' => $st->getFilamentAvatarUrl(),
                'initials' => $this->getInitials($st->name),
            ];
        });

        return response()->json([
            'students' => $formattedStudents,
        ]);
    }

    /**
     * API: Create a new lesson from mobile.
     */
    public function createLesson(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $validated = $request->validate([
            'student_mode' => 'nullable|in:existing,new',
            'student_id' => 'required_if:student_mode,existing|nullable|integer|exists:users,id',
            'new_student_name' => 'required_if:student_mode,new|nullable|string|max:100',
            'new_student_phone' => 'nullable|string|max:30',
            'subject' => 'required|string|max:150',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|in:30,45,60,90,120',
            'price' => 'required|numeric|min:0|max:2000',
            'payment_status' => 'required|in:unpaid,paid',
            'notes' => 'nullable|string|max:1000',
        ]);

        $gate = app(SubscriptionFeatureGate::class);
        $checkStudentId = ($validated['student_mode'] ?? 'existing') === 'existing'
            ? (int) ($validated['student_id'] ?? 0)
            : null;

        if (! $gate->canAddStudent($user, $checkStudentId)) {
            return response()->json([
                'error' => 'Достигнут лимит учеников на тарифе «Стандарт». Перейдите на тариф «Частная практика» для безлимитного добавления учеников.',
                'requires_upgrade' => true,
            ], 403);
        }

        if (($validated['student_mode'] ?? 'existing') === 'new') {
            $phone = strip_tags(trim($validated['new_student_phone'] ?? ''));
            $name = strip_tags(trim($validated['new_student_name'] ?? 'Ученик'));
            $email = 'student_' . time() . '_' . rand(100, 999) . '@edusfera.local';

            $student = User::create([
                'name' => $name,
                'phone' => $phone ?: '+37529' . rand(1000000, 9999999),
                'email' => $email,
                'password' => bcrypt(str()->random(16)),
                'role' => UserRole::Student,
            ]);
            $studentId = $student->id;
        } else {
            $studentId = (int) $validated['student_id'];
            $targetStudent = User::where('id', $studentId)
                ->whereIn('role', [UserRole::Student, UserRole::Parent])
                ->first();

            if (! $targetStudent) {
                return response()->json(['error' => 'Выбранный пользователь не является учеником.'], 422);
            }
        }

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $start = Carbon::parse($validated['start_time'], $displayTz);
        $duration = (int) $validated['duration_minutes'];
        $end = $start->copy()->addMinutes($duration);

        $subject = strip_tags(trim((string) $validated['subject']));
        $notes = strip_tags(trim((string) ($validated['notes'] ?? '')));
        $combinedNotes = $subject !== ''
            ? ("Предмет: {$subject}" . ($notes !== '' ? "\n{$notes}" : ''))
            : ($notes !== '' ? $notes : null);

        $lesson = Lesson::create([
            'tutor_id' => $user->id,
            'student_id' => $studentId,
            'start_time' => $start->utc(),
            'end_time' => $end->utc(),
            'duration_minutes' => $duration,
            'price' => $validated['price'],
            'platform_commission' => 0.00,
            'net_amount' => $validated['price'],
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => $validated['payment_status'],
            'package_code' => 'single',
            'notes' => $combinedNotes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Урок успешно запланирован!',
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->student?->name ?? 'Ученик',
                'student_name' => $lesson->student?->name ?? 'Ученик',
                'subject' => $subject ?: 'Индивидуальное занятие',
                'price' => (float) $lesson->price,
                'start_formatted' => $start->format('H:i'),
                'time_formatted' => $start->format('H:i') . ' — ' . $end->format('H:i'),
                'date_formatted' => $start->translatedFormat('d F (D)'),
                'is_guest_student' => ($validated['student_mode'] ?? 'existing') === 'new',
                'classroom_url' => route('classroom.show', $lesson),
                'student_invite_url' => $lesson->student_invite_url,
            ],
        ]);
    }

    /**
     * API: Mark lesson as paid directly.
     */
    public function markLessonPaid(Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        if ($lesson->status === Lesson::STATUS_CANCELLED) {
            return response()->json(['error' => 'Нельзя отметить оплаченным отменённый урок.'], 422);
        }

        $lesson->update([
            'payment_status' => Lesson::PAYMENT_PAID,
            'status' => $lesson->status === Lesson::STATUS_PENDING ? Lesson::STATUS_CONFIRMED : $lesson->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Оплата зафиксирована! Доступ в виртуальный класс открыт.',
            'payment_status' => Lesson::PAYMENT_PAID,
            'status' => $lesson->status,
        ]);
    }

    /**
     * API: Reschedule an existing lesson.
     */
    public function rescheduleLesson(Request $request, Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        if (in_array($lesson->status, [Lesson::STATUS_CANCELLED, Lesson::STATUS_COMPLETED], true)) {
            return response()->json(['error' => 'Невозможно перенести завершённый или отменённый урок.'], 422);
        }

        $validated = $request->validate([
            'start_time' => 'required|date',
            'duration_minutes' => 'nullable|integer|in:30,45,60,90,120',
        ]);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $start = Carbon::parse($validated['start_time'], $displayTz);
        $duration = (int) ($validated['duration_minutes'] ?? $lesson->duration_minutes ?? 60);
        $end = $start->copy()->addMinutes($duration);

        $lesson->update([
            'start_time' => $start->utc(),
            'end_time' => $end->utc(),
            'duration_minutes' => $duration,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Урок перенесён на ' . $start->format('d.m.Y H:i'),
            'date' => $start->format('Y-m-d'),
            'time_formatted' => $start->format('H:i') . ' — ' . $end->format('H:i'),
        ]);
    }

    /**
     * API: Cancel a lesson.
     */
    public function cancelLesson(Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if ($lesson->tutor_id !== $user->id && $lesson->student_id !== $user->id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        if ($lesson->status === Lesson::STATUS_COMPLETED) {
            return response()->json(['error' => 'Нельзя отменить уже завершённый урок.'], 422);
        }

        if ($lesson->status === Lesson::STATUS_CANCELLED) {
            return response()->json(['error' => 'Урок уже отменён.'], 422);
        }

        $lesson->update([
            'status' => Lesson::STATUS_CANCELLED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Урок успешно отменён.',
            'status' => Lesson::STATUS_CANCELLED,
        ]);
    }

    /**
     * API: Confirm a pending lesson request (1-click tutor approval).
     */
    public function confirmLesson(Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступно только преподавателю этого занятия'], 403);
        }

        if ($lesson->status !== Lesson::STATUS_PENDING) {
            return response()->json(['error' => 'Подтвердить можно только уроки в статусе ожидания.'], 422);
        }

        $gate = app(SubscriptionFeatureGate::class);
        if (! $gate->canAddStudent($user, $lesson->student_id)) {
            return response()->json([
                'error' => 'На тарифе «Стандарт» действует лимит до 5 учеников. Перейдите на тариф «Частная практика» (40 BYN) для работы с неограниченным числом учеников.',
                'requires_upgrade' => true,
            ], 403);
        }

        $lesson->update([
            'status' => Lesson::STATUS_CONFIRMED,
        ]);

        try {
            $lesson->student?->notify(new LessonConfirmedNotification($lesson->fresh()));
        } catch (\Throwable $e) {
            // Log or ignore notification failure
        }

        return response()->json([
            'success' => true,
            'message' => 'Занятие подтверждено! Ученик получил уведомление.',
            'lesson_id' => $lesson->id,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);
    }

    /**
     * API: Assign homework to a lesson's student.
     */
    public function assignHomework(Request $request, Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступно только преподавателю этого занятия'], 403);
        }

        if (! $lesson->student_id) {
            return response()->json(['error' => 'К занятию не привязан ученик.'], 422);
        }

        if ($lesson->status === Lesson::STATUS_CANCELLED) {
            return response()->json(['error' => 'Нельзя назначить задание к отменённому уроку.'], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:3000',
            'due_days' => 'nullable|integer|min:1|max:30',
        ]);

        $dueDays = (int) ($validated['due_days'] ?? 3);
        $dueAt = now('UTC')->addDays($dueDays)->endOfDay();

        $assignment = HomeworkAssignment::create([
            'lesson_id' => $lesson->id,
            'student_id' => $lesson->student_id,
            'tutor_id' => $user->id,
            'title' => strip_tags(trim($validated['title'])),
            'instructions' => strip_tags(trim($validated['instructions'] ?? '')),
            'source' => 'tutor_direct',
            'status' => 'assigned',
            'assigned_at' => now('UTC'),
            'due_at' => $dueAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Домашнее задание успешно отправлено ученику!',
            'homework' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'due_date' => $dueAt->format('d.m.Y'),
            ],
        ]);
    }

    /**
     * API: Get tutor weekly availability schedule.
     */
    public function getAvailability(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $existingAvail = TutorAvailability::where('user_id', $user->id)
            ->get()
            ->keyBy('day_of_week');

        $schedule = collect([
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            0 => 'Воскресенье',
        ])->map(function (string $label, int $day) use ($existingAvail) {
            $row = $existingAvail->get($day);
            return [
                'day_of_week' => $day,
                'day_label' => $label,
                'is_active' => (bool) ($row?->is_active ?? false),
                'start_time' => substr((string) ($row?->start_time ?? '10:00'), 0, 5),
                'end_time' => substr((string) ($row?->end_time ?? '18:00'), 0, 5),
            ];
        })->values()->all();

        return response()->json([
            'availability' => $schedule,
        ]);
    }

    /**
     * API: Save tutor weekly availability schedule.
     */
    public function saveAvailability(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $validated = $request->validate([
            'availability' => 'required|array|min:1|max:7',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.is_active' => 'required|boolean',
            'availability.*.start_time' => ['required', 'string', 'regex:/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/'],
            'availability.*.end_time' => ['required', 'string', 'regex:/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/'],
        ]);

        foreach ($validated['availability'] as $dayItem) {
            if ($dayItem['is_active'] && $dayItem['start_time'] >= $dayItem['end_time']) {
                return response()->json([
                    'error' => 'Время окончания должно быть позже времени начала во всех активных днях.',
                ], 422);
            }
        }

        DB::transaction(function () use ($user, $validated) {
            TutorAvailability::where('user_id', $user->id)->delete();

            foreach ($validated['availability'] as $row) {
                TutorAvailability::create([
                    'user_id' => $user->id,
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => substr((string) $row['start_time'], 0, 5),
                    'end_time' => substr((string) $row['end_time'], 0, 5),
                    'is_active' => (bool) $row['is_active'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'График доступности для онлайн-записи успешно сохранён!',
        ]);
    }

    /**
     * API: Apply promo code (Tutor PRO subscription activation or discount).
     */
    public function applyPromoCode(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $code = strtoupper(trim((string) $request->input('code', '')));
        if (empty($code)) {
            return response()->json(['error' => 'Укажите промокод.'], 422);
        }

        /** @var PromoCode|null $promoModel */
        $promoModel = PromoCode::where('code', $code)->first();
        if (! $promoModel) {
            return response()->json(['error' => 'Промокод не найден или указан неверно.'], 422);
        }

        // Idempotency: If this tutor has already used this promo code and has an active subscription, return success immediately
        $userSub = $user->subscription()->first();
        $hasUsedPromo = $promoModel->usages()->where('user_id', $user->id)->exists();
        if ($hasUsedPromo && $userSub && $userSub->isActive()) {
            return response()->json([
                'success' => true,
                'message' => 'Тариф уже успешно активирован по этому промокоду!',
                'is_activated' => true,
                'plan_name' => $userSub->plan->title(),
                'subscription' => [
                    'plan' => $userSub->plan->value,
                    'plan_name' => $userSub->plan->label(),
                    'is_active' => true,
                    'status' => 'active',
                    'period_end' => $userSub->current_period_ends_at?->format('d.m.Y'),
                    'days_left' => max(0, (int) now()->diffInDays($userSub->current_period_ends_at, false)),
                    'ai_used' => (int) ($userSub->ai_generations_used_this_month ?? 0),
                    'ai_limit' => $userSub->plan->aiMonthlyLimit() ?? 999,
                    'responses_used' => (int) ($userSub->responses_used_this_period ?? $userSub->responses_used_this_month ?? 0),
                    'responses_limit' => $userSub->plan->maxResponsesPerMonth() ?? 20,
                ],
            ]);
        }

        // If the promo code specifies a specific plan, use it automatically!
        if (! empty($promoModel->subscription_plan) && $promoModel->subscription_plan !== 'any') {
            $rawPlan = (string) $promoModel->subscription_plan;
        } else {
            $rawPlan = (string) $request->input('plan', 'pro');
        }

        $planStr = in_array(strtolower($rawPlan), ['start', 'basic'], true) ? 'start' : strtolower($rawPlan);
        $planEnum = \App\Domain\Subscription\Enums\SubscriptionPlan::tryFrom($planStr)
            ?? \App\Domain\Subscription\Enums\SubscriptionPlan::PRO;

        $promoService = app(PromoCodeService::class);
        $result = $promoService->validate(
            $code,
            (float) $planEnum->monthlyPriceByn(),
            $user,
            [
                'order_type' => 'subscription',
                'plan' => $planEnum->value,
            ]
        );

        if (! $result['valid']) {
            // Edge-case check: If the validation failed because user already used it, but user has active subscription
            $userSub = $user->subscription()->first();
            if ($userSub && $userSub->isActive() && $promoModel->usages()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Тариф уже успешно активирован по этому промокоду!',
                    'is_activated' => true,
                    'plan_name' => $userSub->plan->title(),
                    'subscription' => [
                        'plan' => $userSub->plan->value,
                        'plan_name' => $userSub->plan->label(),
                        'is_active' => true,
                        'status' => 'active',
                        'period_end' => $userSub->current_period_ends_at?->format('d.m.Y'),
                        'days_left' => max(0, (int) now()->diffInDays($userSub->current_period_ends_at, false)),
                        'ai_used' => (int) ($userSub->ai_generations_used_this_month ?? 0),
                        'ai_limit' => $userSub->plan->aiMonthlyLimit() ?? 999,
                        'responses_used' => (int) ($userSub->responses_used_this_period ?? $userSub->responses_used_this_month ?? 0),
                        'responses_limit' => $userSub->plan->maxResponsesPerMonth() ?? 20,
                    ],
                ]);
            }

            return response()->json([
                'error' => $result['error'] ?? 'Недействительный или истекший промокод.',
            ], 422);
        }

        /** @var PromoCode $promo */
        $promo = $result['promo_code'];

        // If promo code provides free subscription or 100% discount, activate immediately!
        $isFree = $promo->isFreeSubscription() || ((float) ($result['final_amount'] ?? 1.0) <= 0.01);
        if ($isFree) {
            try {
                $activation = $promoService->activateFreeSubscriptionViaPromo($promo, $user, $planEnum->value);
            } catch (\Throwable $e) {
                // If activation threw (e.g. race condition), check if subscription was actually activated
                $userSub = $user->subscription()->first();
                if ($userSub && $userSub->isActive() && $promoModel->usages()->where('user_id', $user->id)->exists()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Тариф успешно активирован!',
                        'is_activated' => true,
                        'plan_name' => $userSub->plan->title(),
                        'subscription' => [
                            'plan' => $userSub->plan->value,
                            'plan_name' => $userSub->plan->label(),
                            'is_active' => true,
                            'status' => 'active',
                            'period_end' => $userSub->current_period_ends_at?->format('d.m.Y'),
                            'days_left' => max(0, (int) now()->diffInDays($userSub->current_period_ends_at, false)),
                            'ai_used' => (int) ($userSub->ai_generations_used_this_month ?? 0),
                            'ai_limit' => $userSub->plan->aiMonthlyLimit() ?? 999,
                            'responses_used' => (int) ($userSub->responses_used_this_period ?? $userSub->responses_used_this_month ?? 0),
                            'responses_limit' => $userSub->plan->maxResponsesPerMonth() ?? 20,
                        ],
                    ]);
                }

                return response()->json([
                    'error' => $e->getMessage() ?: 'Ошибка активации промокода.',
                ], 422);
            }

            $sub = $user->subscription()->first();

            $label = ($activation['is_lifetime'] ?? false)
                ? 'Бессрочный доступ к тарифу «'.$planEnum->title().'» успешно активирован!'
                : sprintf('Тариф «%s» на %d мес. успешно активирован бесплатно!', $planEnum->title(), $activation['months'] ?? 1);

            return response()->json([
                'success' => true,
                'message' => $label,
                'is_activated' => true,
                'plan_name' => $planEnum->title(),
                'subscription' => [
                    'plan' => $sub->plan->value,
                    'plan_name' => $sub->plan->label(),
                    'is_active' => true,
                    'status' => 'active',
                    'period_end' => $sub->current_period_ends_at?->format('d.m.Y'),
                    'days_left' => max(0, (int) now()->diffInDays($sub->current_period_ends_at, false)),
                    'ai_used' => (int) ($sub->ai_generations_used_this_month ?? 0),
                    'ai_limit' => $sub->plan->aiMonthlyLimit() ?? 999,
                    'responses_used' => (int) ($sub->responses_used_this_period ?? $sub->responses_used_this_month ?? 0),
                    'responses_limit' => $sub->plan->maxResponsesPerMonth() ?? 20,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Промокод действителен! Предоставляется скидка: ' . ($promo->discount_amount ?? $promo->discount_percent . '%'),
            'discount' => $promo->discount_amount ?? $promo->discount_percent,
            'is_activated' => false,
        ]);
    }

    /**
     * API: Generate Quick Lesson Prep (Maya AI).
     */
    public function generateLessonPrep(Request $request, Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступно только преподавателю урока'], 403);
        }

        $gate = app(SubscriptionFeatureGate::class);
        if (! $gate->canUseAiTools($user)) {
            return response()->json([
                'error' => 'Лимит ИИ-запросов исчерпан. Перейдите на тариф «Частная практика» для безлимитного доступа.',
                'requires_upgrade' => true,
            ], 403);
        }
        $gate->incrementAiGenerationUsage($user);

        $customTopic = trim((string) $request->input('custom_topic', ''));
        $subject = $lesson->subject ?: 'Занятие';
        $topic = $customTopic !== '' ? $customTopic : ($lesson->subject ?: 'Подготовка к ЦТ/ЦЭ 2026: базовый модуль');

        $studentName = $lesson->student?->name ?? 'Ученик';
        $tutorName = $user->name;

        $prompt = "Ты ведущий эксперт-методист образовательной платформы Edusfera.by.\n" .
            "Преподаватель {$tutorName} через 10 минут начинает урок с учеником {$studentName}.\n" .
            "Предмет: {$subject}. Тема текущего занятия: «{$topic}».\n\n" .
            "Составь конспект-шпаргалку к уроку на 1 экран (шпаргалка для преподавателя перед занятием):\n" .
            "1. **⚡ Разминка (3 мин):** 3 быстрых устных вопроса на проверку понятий/формул.\n" .
            "2. **⚠️ Ловушки РИКЗ (ЦТ/ЦЭ 2026):** 2 типичные ошибки абитуриентов в этой теме.\n" .
            "3. **📝 Практический блок (3 задачи):** краткие условия 3 ключевых задач для совместного решения на интерактивной доске.\n" .
            "4. **🎯 Контрольный вопрос в финале (1 мин)** для быстрой фиксации усвоения темы.\n\n" .
            "Пиши емко, структурировано, формулы оформляй понятно.";

        $system = 'Ты опытный методист-наставник Edusfera.by. Твой конспект должен читаться за 30 секунд.';

        try {
            $plan = app(GeminiService::class)->generateText($prompt, $system);
        } catch (\Throwable $e) {
            $plan = null;
        }

        if (empty(trim((string) $plan))) {
            $plan = "### ⚡ 1. Разминка (3 мин)\n1. Базовые определения и формулы темы «{$topic}» ({$subject}).\n2. Проверка понимания ключевых условий и ограничений.\n3. Вопрос на взаимосвязь с пройденным ранее материалом.\n\n### ⚠️ 2. Ловушки РИКЗ (ЦТ/ЦЭ 2026)\n- Типовые вычислительные неточности и невнимательность в знаках.\n- Подводные камни формулировок в тестах спецификации РИКЗ.\n\n### 📝 3. Практика (3 задачи)\n1. Задача базового уровня на первичное закрепление темы.\n2. Комбинированная задача уровня части А ЦТ.\n3. Задача с логическим усложнением уровня части Б.\n\n### 🎯 4. Контрольный вопрос в финале\n- Сформулировать вывод и контрольное правило самопроверки.";
        }

        return response()->json([
            'success' => true,
            'topic' => $topic,
            'plan' => $plan,
        ]);
    }

    /**
     * API: Request payout.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $balance = $user->tutorBalance;
        $available = (float) ($balance?->available_amount ?? 0.0);

        if ($available <= 0) {
            return response()->json(['error' => 'Нет доступных средств к выводу'], 422);
        }

        $validated = $request->validate([
            'card_pan' => 'nullable|string|max:30',
        ]);

        try {
            $paymentService = app(\App\Services\Payment\PaymentService::class);
            $paymentService->requestPayout($user->id);

            return response()->json([
                'success' => true,
                'message' => "Заявка на вывод {$available} BYN принята в обработку. Средства поступят в течение 1 рабочего дня.",
                'available' => 0.0,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->validator->errors()->first('payout') ?: 'У вас нет доступных средств для вывода.',
            ], 422);
        }
    }

    /**
     * API: Export .ics schedule.
     */
    public function exportIcs(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $lessons = Lesson::query()
            ->with(['tutor', 'student'])
            ->where(function ($q) use ($user) {
                if ($user->isTutor()) {
                    $q->where('tutor_id', $user->id);
                } else {
                    $q->where('student_id', $user->id);
                }
            })
            ->where('start_time', '>=', Carbon::now('UTC')->subMonth())
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->get();

        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Edusfera//Mobile 1.0//RU\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "X-WR-CALNAME:Edusfera Расписание\r\n";
        $icsContent .= "X-WR-TIMEZONE:{$displayTz}\r\n";

        foreach ($lessons as $lesson) {
            $dtStart = $lesson->start_time?->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $lesson->end_time?->setTimezone('UTC')->format('Ymd\THis\Z');
            $summary = 'Урок Edusfera: ' . ($user->isTutor() ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'));
            $description = 'Онлайн-урок на платформе Edusfera. Ссылка: ' . route('classroom.show', $lesson);

            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:edusfera-lesson-{$lesson->id}@edusfera.by\r\n";
            $icsContent .= 'DTSTAMP:' . Carbon::now('UTC')->format('Ymd\THis\Z') . "\r\n";
            $icsContent .= "DTSTART:{$dtStart}\r\n";
            $icsContent .= "DTEND:{$dtEnd}\r\n";
            $icsContent .= "SUMMARY:{$summary}\r\n";
            $icsContent .= "DESCRIPTION:{$description}\r\n";
            $icsContent .= 'URL:' . route('classroom.show', $lesson) . "\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "END:VEVENT\r\n";
        }

        $icsContent .= "END:VCALENDAR\r\n";

        return response()->streamDownload(
            function () use ($icsContent) {
                echo $icsContent;
            },
            'edusfera-schedule.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="edusfera-schedule.ics"',
            ]
        );
    }

    /**
     * API: Get chats list.
     */
    public function getChats(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $conversations = Conversation::query()
            ->where('tutor_id', $user->id)
            ->orWhere('student_id', $user->id)
            ->with(['tutor.tutorProfile', 'student'])
            ->orderByDesc('updated_at')
            ->take(20)
            ->get()
            ->map(function ($conv) use ($user) {
                $isTutor = $user->id === $conv->tutor_id;
                $otherUser = $isTutor ? $conv->student : $conv->tutor;

                return [
                    'id' => $conv->id,
                    'partner_name' => $otherUser?->name ?? 'Собеседник',
                    'partner_role' => $isTutor ? 'student' : 'tutor',
                    'partner_id' => $otherUser?->id,
                    'partner_avatar' => $otherUser?->getFilamentAvatarUrl(),
                    'partner_initials' => $this->getInitials($otherUser?->name ?? 'Собеседник'),
                    'last_message' => $conv->last_message_text ?? 'Нажмите, чтобы открыть переписку',
                    'last_message_time' => $conv->last_message_at?->diffForHumans() ?? $conv->updated_at->diffForHumans(),
                    'unread_count' => (int) ($conv->unread_count ?? 0),
                ];
            });

        return response()->json([
            'chats' => $conversations,
        ]);
    }

    /**
     * API: Upload user avatar from mobile device.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $file = $request->file('avatar');
        $rawExt = strtolower($file->guessExtension() ?: 'jpg');
        $extension = match ($rawExt) {
            'png' => 'png',
            'webp' => 'webp',
            default => 'jpg',
        };

        $imageInfo = @getimagesize($file->getRealPath());
        if (! $imageInfo) {
            return response()->json(['error' => 'Файл не является корректным изображением'], 422);
        }

        $filename = 'avatar_' . $user->id . '_' . time() . '_' . str()->random(8) . '.' . $extension;
        $path = $file->storeAs('avatars', $filename, 'public');

        if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = $path;
        $user->save();

        if ($user->tutorProfile) {
            $user->tutorProfile->avatar_path = $path;
            $user->tutorProfile->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Фотография профиля успешно обновлена',
            'avatar_url' => $user->getFilamentAvatarUrl(),
        ]);
    }

    /**
     * API: Get wallet / billing transactions for mobile.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $transactions = Transaction::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->isTutor()) {
                    $q->orWhereHas('lesson', function ($lq) use ($user) {
                        $lq->where('tutor_id', $user->id);
                    });
                }
            })
            ->with(['lesson'])
            ->orderByDesc('created_at')
            ->take(30)
            ->get()
            ->map(function (Transaction $tx) use ($user) {
                return [
                    'id' => $tx->id,
                    'amount' => (float) $tx->amount,
                    'net_amount' => (float) ($tx->net_amount ?? $tx->amount),
                    'status' => $tx->status,
                    'payment_method' => $tx->payment_method ?? 'Банковская карта',
                    'date' => $tx->created_at?->format('d.m.Y H:i') ?? '',
                    'description' => $tx->lesson ? ('Оплата занятия #' . $tx->lesson_id) : 'Операция по балансу',
                    'is_incoming' => $user->isTutor() ? true : false,
                ];
            });

        return response()->json([
            'transactions' => $transactions,
        ]);
    }

    /**
     * API: Toggle tutor catalog visibility.
     */
    public function toggleCatalogActive(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $profile = TutorProfile::where('user_id', $user->id)->firstOrFail();
        $profile->is_public = ! $profile->is_public;
        $profile->save();

        return response()->json([
            'success' => true,
            'is_active' => (bool) $profile->is_public,
            'message' => $profile->is_public ? 'Анкета опубликована в каталоге' : 'Анкета скрыта из каталога',
        ]);
    }

    /**
     * API: Get notifications list and unread count.
     */
    public function getNotifications(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->limit(30)->get()->map(function ($n) {
            $data = is_array($n->data) ? $n->data : [];
            $title = $data['title'] ?? 'Уведомление';
            $message = $data['message'] ?? $data['body'] ?? $data['text'] ?? 'Событие в личном кабинете';
            $type = 'system';
            $baseName = class_basename($n->type);
            if (str_contains($baseName, 'Lesson')) $type = 'lesson';
            elseif (str_contains($baseName, 'Payment') || str_contains($baseName, 'Payout')) $type = 'finance';
            elseif (str_contains($baseName, 'Chat') || str_contains($baseName, 'Message')) $type = 'chat';
            elseif (str_contains($baseName, 'Subscription')) $type = 'subscription';

            return [
                'id' => (string) $n->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->diffForHumans() ?? 'только что',
                'timestamp' => $n->created_at?->format('d.m.Y H:i') ?? '',
            ];
        })->values()->all();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * API: Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'message' => 'Все уведомления прочитаны',
        ]);
    }

    /**
     * API: Mark single notification as read.
     */
    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * API: Update profile settings (rates, headline, bio, education, methodology, experience, subjects, formats, comfort vision).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:4000',
            'education_summary' => 'nullable|string|max:2000',
            'teaching_methodology' => 'nullable|string|max:2000',
            'achievements' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0|max:70',
            'legal_status' => 'nullable|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:5|max:500',
            'subjects' => 'nullable|array',
            'subjects.*' => 'string|max:60',
            'audiences' => 'nullable|array',
            'audiences.*' => 'string|max:60',
            'lesson_formats' => 'nullable|array',
            'lesson_formats.*' => 'string|max:30',
            'comfort_vision_mode' => 'nullable|boolean',
            'telegram_username' => 'nullable|string|max:100',
        ]);

        if (isset($validated['name'])) {
            $cleanName = strip_tags(trim($validated['name']));
            if ($cleanName !== '') {
                $user->name = $cleanName;
            }
        }
        if (isset($validated['phone'])) {
            $user->phone = strip_tags(trim($validated['phone']));
        }
        $user->save();

        if ($user->isTutor()) {
            $profile = TutorProfile::firstOrCreate(['user_id' => $user->id]);

            if (isset($validated['bio'])) {
                $profile->bio = strip_tags(trim($validated['bio']));
            } elseif (isset($validated['headline'])) {
                $profile->bio = strip_tags(trim($validated['headline']));
            }
            if (isset($validated['education_summary'])) {
                $profile->education_summary = strip_tags(trim($validated['education_summary']));
            }
            if (isset($validated['teaching_methodology'])) {
                $profile->teaching_methodology = strip_tags(trim($validated['teaching_methodology']));
            }
            if (isset($validated['achievements'])) {
                $profile->achievements = strip_tags(trim($validated['achievements']));
            }
            if (isset($validated['experience_years'])) {
                $profile->experience_years = (int) $validated['experience_years'];
            }
            if (isset($validated['legal_status'])) {
                $profile->legal_status = strip_tags(trim($validated['legal_status']));
            }
            if (isset($validated['hourly_rate'])) {
                $profile->price_per_hour = (float) $validated['hourly_rate'];
            }
            if (isset($validated['subjects'])) {
                $profile->subjects = array_values(array_filter(array_map(fn($s) => strip_tags(trim((string)$s)), $validated['subjects'])));
            }
            if (isset($validated['audiences'])) {
                $profile->audiences = array_values(array_filter(array_map(fn($a) => strip_tags(trim((string)$a)), $validated['audiences'])));
            }
            if (isset($validated['lesson_formats'])) {
                $profile->lesson_formats = array_values(array_filter(array_map(fn($f) => strip_tags(trim((string)$f)), $validated['lesson_formats'])));
            }
            if (isset($validated['comfort_vision_mode'])) {
                $profile->comfort_vision_mode = (bool) $validated['comfort_vision_mode'];
            }
            if (isset($validated['telegram_username'])) {
                $cleanTg = preg_replace('/[^a-zA-Z0-9_]/', '', ltrim(trim($validated['telegram_username']), '@'));
                $profile->telegram_username = $cleanTg ?: null;
            }

            $profile->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки профиля успешно сохранены',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'avatar_url' => $user->getFilamentAvatarUrl(),
                'initials' => $this->getInitials($user->name),
            ],
            'tutor_profile' => isset($profile) ? [
                'id' => $profile->id,
                'headline' => $profile->headline ?? $profile->bio ?? 'Репетитор Edusfera',
                'bio' => $profile->bio ?? '',
                'education_summary' => $profile->education_summary ?? '',
                'teaching_methodology' => $profile->teaching_methodology ?? '',
                'achievements' => $profile->achievements ?? '',
                'experience_years' => (int) ($profile->experience_years ?? 0),
                'legal_status' => $profile->legal_status ?? 'none',
                'audiences' => is_array($profile->audiences) ? $profile->audiences : [],
                'hourly_rate' => (float) ($profile->price_per_hour ?? 35.0),
                'is_active' => (bool) $profile->is_public,
                'is_verified' => (bool) ($profile->is_verified ?? false),
                'verification_status' => $profile->verification_status ?? ($profile->is_verified ? 'approved' : ($profile->diploma_path ? 'pending' : 'unverified')),
                'diploma_path' => $profile->diploma_path ? asset('storage/' . $profile->diploma_path) : null,
                'diploma_filename' => $profile->diploma_path ? basename($profile->diploma_path) : null,
                'subjects' => $profile->subjects ?? [],
                'lesson_formats' => $profile->lesson_formats ?? ['online'],
                'comfort_vision_mode' => (bool) ($profile->comfort_vision_mode ?? false),
                'telegram_username' => $profile->telegram_username,
            ] : null,
        ]);
    }

    /**
     * API: Upload tutor diploma/certificate for technical admin verification.
     */
    public function uploadDiploma(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступ запрещен: только для преподавателей'], 403);
        }

        $request->validate([
            'diploma' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ], [
            'diploma.required' => 'Выберите файл диплома или сертификата',
            'diploma.mimes' => 'Поддерживаются форматы JPG, PNG, WEBP, PDF',
            'diploma.max' => 'Максимальный размер файла — 10 МБ',
        ]);

        $profile = TutorProfile::firstOrCreate(['user_id' => $user->id]);

        $path = $request->file('diploma')->store('diplomas', 'public');
        $profile->diploma_path = $path;
        $profile->verification_status = 'pending';
        $profile->save();

        // Отправка техническому администратору на модерацию
        try {
            app(\App\Services\TutorVerificationService::class)->submitForReview($profile);
        } catch (\Throwable $e) {
            \Log::warning('[Diploma Upload] Notification warning: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Диплом успешно загружен и отправлен техническому администратору на проверку!',
            'diploma_path' => asset('storage/' . $path),
            'diploma_filename' => basename($path),
            'verification_status' => 'pending',
            'is_verified' => false,
        ]);
    }

    /**
     * API: Checkout subscription plan via Alfa-Bank acquiring or Promo code.
     * Absolutely NO free activation without valid payment or free promo code.
     */
    public function checkoutSubscription(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'plan' => 'required|string|in:start,basic,pro,premium',
            'billing_period' => 'nullable|string|in:monthly,yearly',
            'promo_code' => 'nullable|string|max:50',
        ]);

        $planStr = in_array($validated['plan'], ['start', 'basic'], true) ? 'start' : $validated['plan'];
        $planEnum = \App\Domain\Subscription\Enums\SubscriptionPlan::tryFrom($planStr)
            ?? \App\Domain\Subscription\Enums\SubscriptionPlan::PRO;

        $period = $validated['billing_period'] ?? 'monthly';
        $durationMonths = $period === 'yearly' ? 12 : 1;

        // Base price calculation
        $basePriceByn = $period === 'yearly'
            ? ($planEnum->monthlyPriceByn() * 12 * 0.8) // 20% discount for annual
            : (float) $planEnum->monthlyPriceByn();

        $promoCodeStr = strtoupper(trim((string) ($validated['promo_code'] ?? '')));
        $discountAmount = 0.0;
        $promoModel = null;

        if (! empty($promoCodeStr)) {
            $promoService = app(\App\Services\PromoCodeService::class);
            $promoResult = $promoService->validate(
                $promoCodeStr,
                $basePriceByn,
                $user,
                ['order_type' => 'subscription', 'plan' => $planEnum->value]
            );

            if ($promoResult['valid']) {
                $promoModel = $promoResult['promo_code'];

                // If promo code gives 100% free subscription, activate immediately!
                if ($promoModel->isFreeSubscription()) {
                    $activation = $promoService->activateFreeSubscriptionViaPromo($promoModel, $user, $planEnum->value);
                    $sub = $user->subscription()->first();

                    return response()->json([
                        'success' => true,
                        'is_activated' => true,
                        'message' => "Тариф «{$planEnum->title()}» успешно активирован по промокоду {$promoModel->code}!",
                        'subscription' => [
                            'plan' => $sub->plan->value,
                            'plan_name' => $sub->plan->label(),
                            'is_active' => true,
                            'status' => 'active',
                            'period_end' => $sub->current_period_ends_at?->format('d.m.Y'),
                            'days_left' => max(0, (int) now()->diffInDays($sub->current_period_ends_at, false)),
                        ],
                    ]);
                }

                $discountAmount = (float) ($promoResult['discount_amount'] ?? 0.0);
            } else {
                return response()->json([
                    'error' => $promoResult['error'] ?? 'Недействительный промокод',
                ], 422);
            }
        }

        $finalAmountByn = max(0.01, round($basePriceByn - $discountAmount, 2));

        // Get or create inactive subscription record
        $subscription = \App\Domain\Subscription\Models\Subscription::firstOrCreate(
            ['tutor_id' => $user->id],
            [
                'plan' => $planEnum,
                'status' => \App\Domain\Subscription\Enums\SubscriptionStatus::EXPIRED,
                'current_period_starts_at' => now(),
                'current_period_ends_at' => now(),
            ]
        );

        $subService = app(\App\Domain\Subscription\Services\SubscriptionService::class);
        $invoice = $subService->createInvoice($subscription, $planEnum, $durationMonths);

        if ($promoModel) {
            $invoice->update([
                'promo_code_id' => $promoModel->id,
                'discount_kopecks' => (int) round($discountAmount * 100),
                'amount_kopecks' => (int) round($finalAmountByn * 100),
            ]);
        }

        $gateway = app(\App\Services\Payment\PaymentGatewayInterface::class);

        try {
            $result = $gateway->createPayment([
                'amount' => $finalAmountByn,
                'subscription' => true,
                'user_id' => $user->id,
                'plan' => $planEnum->value,
                'invoice_id' => $invoice->id,
                'return_url' => route('cabinet', ['payment' => 'success']),
                'fail_url' => route('cabinet', ['payment' => 'failed']),
                'description' => sprintf(
                    'Подписка Edusfera: «%s» (%s)%s',
                    $planEnum->title(),
                    $period === 'yearly' ? 'на 1 год' : 'на 1 месяц',
                    $promoModel ? " [промокод {$promoModel->code}]" : ''
                ),
            ]);

            if (! ($result['success'] ?? false)) {
                return response()->json([
                    'error' => $result['message'] ?? 'Не удалось связаться с платёжным шлюзом банка. Попробуйте ещё раз.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $result['redirect_url'] ?? null,
                'invoice_id' => $invoice->id,
                'amount' => $finalAmountByn,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[Subscription Checkout] Failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка при обращении к платёжному шлюзу: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Change or select tutor subscription plan (delegates to checkoutSubscription).
     */
    public function changeSubscriptionPlan(Request $request): JsonResponse
    {
        return $this->checkoutSubscription($request);
    }

    /**
     * API: Safely change user password from mobile cabinet.
     */
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Введите текущий пароль',
            'new_password.required' => 'Введите новый пароль',
            'new_password.min' => 'Новый пароль должен содержать не менее 8 символов',
            'new_password.confirmed' => 'Подтверждение нового пароля не совпадает',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'error' => 'Текущий пароль указан неверно',
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно изменён',
        ]);
    }

    /**
     * Compute full NPD dataset for tutor.
     */
    public function computeNpdData(User $user): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth()->utc();
        $endOfMonth = $now->copy()->endOfMonth()->utc();
        $startOfYear = $now->copy()->startOfYear()->utc();

        $lessonsQuery = Lesson::query()
            ->with(['student', 'parent'])
            ->where('tutor_id', $user->id)
            ->where(function ($query) {
                $query->where('status', Lesson::STATUS_COMPLETED)
                    ->orWhere('payment_status', Lesson::PAYMENT_PAID);
            });

        $monthGross = (float) (clone $lessonsQuery)
            ->where('start_time', '>=', $startOfMonth)
            ->where('start_time', '<=', $endOfMonth)
            ->sum('price');

        $yearGross = (float) (clone $lessonsQuery)
            ->where('start_time', '>=', $startOfYear)
            ->where('start_time', '<=', $endOfMonth)
            ->sum('price');

        $applyFirstTimeDeduction = (bool) session()->get('npd_first_time_deduction_'.$user->id, true);
        $deductionTotal = 2000.00;
        $deductionRemaining = max(0.00, $deductionTotal - $yearGross);
        $deductionUsed = min($deductionTotal, $yearGross);

        if ($applyFirstTimeDeduction) {
            $taxableIncomeMonth = max(0.00, $monthGross - $deductionRemaining);
            $taxDueMonth = round($taxableIncomeMonth * 0.10, 2);
            $taxSaved = round(min($monthGross, $deductionRemaining) * 0.10, 2);
        } else {
            $taxableIncomeMonth = $monthGross;
            $taxDueMonth = round($monthGross * 0.10, 2);
            $taxSaved = 0.00;
        }

        $nextMonth = $now->copy()->addMonth();
        $paymentDeadline = '22 '.$nextMonth->translatedFormat('F Y');
        $notificationDeadline = '10 '.$nextMonth->translatedFormat('F Y');

        $receiptsIssuedCount = (clone $lessonsQuery)->whereNotNull('npd_receipt_issued_at')->count();
        $receiptsPendingCount = (clone $lessonsQuery)->whereNull('npd_receipt_issued_at')->count();

        $lessons = (clone $lessonsQuery)
            ->latest('start_time')
            ->limit(30)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'student_name' => $l->student?->name ?? 'Ученик',
                    'subject' => $l->subject ?: 'Занятие',
                    'date' => $l->start_time ? Carbon::parse($l->start_time)->setTimezone(config('booking.display_timezone', 'Europe/Minsk'))->format('d.m.Y H:i') : '',
                    'price' => (float) $l->price,
                    'status' => $l->status,
                    'is_receipt_issued' => $l->npd_receipt_issued_at !== null,
                    'receipt_number' => $l->npd_receipt_number,
                    'receipt_issued_at' => $l->npd_receipt_issued_at ? Carbon::parse($l->npd_receipt_issued_at)->format('d.m.Y H:i') : null,
                ];
            })->values()->all();

        return [
            'month_gross' => $monthGross,
            'year_gross' => $yearGross,
            'tax_due_month' => $taxDueMonth,
            'tax_saved' => $taxSaved,
            'deduction_remaining' => $deductionRemaining,
            'deduction_used' => $deductionUsed,
            'deduction_total' => $deductionTotal,
            'apply_first_time_deduction' => $applyFirstTimeDeduction,
            'payment_deadline' => $paymentDeadline,
            'notification_deadline' => $notificationDeadline,
            'receipts_issued_count' => $receiptsIssuedCount,
            'receipts_pending_count' => $receiptsPendingCount,
            'lessons' => $lessons,
        ];
    }

    /**
     * API: Get NPD statistics and lessons.
     */
    public function getNpd(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        return response()->json([
            'success' => true,
            'npd' => $this->computeNpdData($user),
        ]);
    }

    /**
     * API: Toggle first-time 2000 BYN deduction.
     */
    public function toggleNpdDeduction(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $current = (bool) session()->get('npd_first_time_deduction_'.$user->id, true);
        $next = ! $current;
        session()->put('npd_first_time_deduction_'.$user->id, $next);

        return response()->json([
            'success' => true,
            'apply_first_time_deduction' => $next,
            'npd' => $this->computeNpdData($user),
            'message' => $next ? 'Льготный вычет 2 000 руб. включён' : 'Льготный вычет отключён',
        ]);
    }

    /**
     * API: Generate NPD receipts for all pending lessons in 1 click.
     */
    public function generateOneClickReceipts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor()) {
            return response()->json(['error' => 'Доступно только репетиторам'], 403);
        }

        $pendingLessons = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where(function ($query) {
                $query->where('status', Lesson::STATUS_COMPLETED)
                    ->orWhere('payment_status', Lesson::PAYMENT_PAID);
            })
            ->whereNull('npd_receipt_issued_at')
            ->get();

        if ($pendingLessons->isEmpty()) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'message' => 'Все чеки уже сформированы',
                'npd' => $this->computeNpdData($user),
            ]);
        }

        foreach ($pendingLessons as $lesson) {
            $lesson->update([
                'npd_receipt_issued_at' => now(),
                'npd_receipt_number' => 'МНС-'.now()->format('Ymd').'-'.$lesson->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => $pendingLessons->count(),
            'message' => "Сформированы данные для {$pendingLessons->count()} чеков в 1 клик",
            'npd' => $this->computeNpdData($user),
        ]);
    }

    /**
     * API: Update single lesson NPD receipt status.
     */
    public function updateNpdReceipt(Request $request, Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isTutor() || $lesson->tutor_id !== $user->id) {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'action' => 'nullable|string|in:issue,unmark',
            'receipt_number' => 'nullable|string|max:60|regex:/^[a-zA-Z0-9\-_А-Яа-яЁё ]+$/u',
        ]);

        $action = $validated['action'] ?? 'issue';
        $receiptNumber = strip_tags(trim((string) ($validated['receipt_number'] ?? '')));

        if ($action === 'unmark') {
            $lesson->update([
                'npd_receipt_issued_at' => null,
                'npd_receipt_number' => null,
            ]);
            $msg = 'Статус чека сброшен';
        } else {
            $number = ! empty($receiptNumber) ? $receiptNumber : ('МНС-'.now()->format('Ymd').'-'.$lesson->id);
            $lesson->update([
                'npd_receipt_issued_at' => now(),
                'npd_receipt_number' => $number,
            ]);
            $msg = "Чек {$number} успешно сохранен";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'npd' => $this->computeNpdData($user),
        ]);
    }

    /**
     * API: Get student homework assignments.
     */
    public function getHomework(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isStudent = $user->isStudent();
        $isParent = $user->isParent();

        if (! $isStudent && ! $isParent) {
            return response()->json(['error' => 'Доступно только ученикам и родителям'], 403);
        }

        $assignments = HomeworkAssignment::query()
            ->with(['lesson.tutor', 'studentGoal'])
            ->where('student_id', $user->id)
            ->latest('assigned_at')
            ->latest('id')
            ->get()
            ->map(function ($hw) {
                return [
                    'id' => $hw->id,
                    'title' => $hw->title,
                    'instructions' => $hw->instructions,
                    'status' => $hw->status,
                    'assigned_at' => $hw->assigned_at?->format('d.m.Y'),
                    'due_at' => $hw->due_at?->format('d.m.Y'),
                    'is_overdue' => $hw->due_at ? ($hw->due_at->isPast() && $hw->status !== 'completed') : false,
                    'completed_at' => $hw->completed_at?->format('d.m.Y H:i'),
                    'tutor_name' => $hw->tutor?->name ?? 'Преподаватель',
                    'tutor_avatar' => $hw->tutor?->getFilamentAvatarUrl(),
                    'subject' => $hw->lesson?->subject ?? 'Занятие',
                ];
            });

        return response()->json([
            'assignments' => $assignments,
            'assigned_count' => $assignments->where('status', 'assigned')->count(),
            'completed_count' => $assignments->where('status', 'completed')->count(),
        ]);
    }

    /**
     * API: Student marks homework completed.
     */
    public function completeHomework(Request $request, HomeworkAssignment $homework): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if ($homework->student_id !== $user->id) {
            return response()->json(['error' => 'Задание принадлежит другому ученику'], 403);
        }

        $updated = app(HomeworkService::class)->markCompleted($homework, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Домашнее задание отмечено выполненным!',
            'homework' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'completed_at' => $updated->completed_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * API: Student topup wallet via payment gateway.
     */
    public function topupWallet(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isStudent() && ! $user->isParent()) {
            return response()->json(['error' => 'Пополнение доступно для учеников и родителей'], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:5000'],
        ]);

        $amount = (float) $validated['amount'];
        $gateway = app(PaymentGatewayInterface::class);
        $response = $gateway->createPayment([
            'wallet_topup' => true,
            'user_id' => $user->id,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'BYN',
        ]);

        if (! ($response['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $response['message'] ?? 'Ошибка платежного шлюза',
            ], 422);
        }

        $responseStatus = (string) ($response['status'] ?? '');
        $status = ($responseStatus === 'success') ? 'success' : 'pending';

        DB::transaction(function () use ($user, $amount, $status, $response) {
            WalletTopup::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => 'BYN',
                'status' => $status,
                'gateway_transaction_id' => $response['gateway_transaction_id'] ?? null,
                'gateway_response' => $response,
            ]);

            if ($status === 'success') {
                $studentBalance = $user->studentBalance()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance_amount' => 0.0]
                );
                $studentBalance->increment('balance_amount', $amount);
            }
        });

        return response()->json([
            'success' => true,
            'status' => $status,
            'redirect_url' => $response['redirect_url'] ?? null,
            'message' => $status === 'success' ? "Баланс успешно пополнен на {$amount} BYN" : 'Платёж инициирован. Ожидаем подтверждения от банка.',
        ]);
    }

    private function getInitials(?string $name): string
    {
        if (empty($name)) {
            return 'ED';
        }

        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }
}
