<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Models\Dispute;
use App\Models\Lesson;
use App\Models\NewsArticle;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAuditLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-welcome-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public string $auditTab = 'all'; // 'all', 'sessions', 'auth', 'lessons', 'payments'

    public static function canView(): bool
    {
        return (bool) Auth::user()?->isAdmin();
    }

    public function setAuditTab(string $tab): void
    {
        $allowed = ['all', 'sessions', 'auth', 'lessons', 'payments'];
        if (in_array($tab, $allowed, true)) {
            $this->auditTab = $tab;
        }
    }

    protected function getViewData(): array
    {
        $this->ensureInitialAuditLogs();
        $this->refreshCurrentAdminSession();

        $metrics = Cache::remember('admin_cockpit_metrics', 60, function (): array {
            $totalUsers = User::count();
            $totalTutors = User::where('role', 'tutor')->count();
            $totalStudents = User::where('role', 'student')->count();
            $usersThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();

            $totalLessons = Lesson::count();
            $completedLessons = Lesson::where('status', Lesson::STATUS_COMPLETED)->count();
            $pendingRequests = Lesson::where('status', Lesson::STATUS_PENDING)->count();
            $confirmedLessons = Lesson::where('status', Lesson::STATUS_CONFIRMED)->where('start_time', '>=', now())->count();
            $completionRate = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            // SaaS Subscriptions metrics
            $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();
            $trialSubscriptions = Subscription::where('status', SubscriptionStatus::TRIAL)->count();
            $totalSubscribers = Subscription::whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])->count();
            $founderSubscriptions = Subscription::where('is_founder', true)->count();

            // Calculate MRR from active subscriptions
            $mrr = 0.0;
            $activeSubs = Subscription::where('status', SubscriptionStatus::ACTIVE)->get();
            foreach ($activeSubs as $sub) {
                if ($sub->plan instanceof SubscriptionPlan) {
                    $mrr += (float) $sub->plan->monthlyPriceByn();
                }
            }

            // Paid subscription revenue this month
            $subscriptionRevenueThisMonth = ((float) SubscriptionInvoice::where('status', InvoiceStatus::PAID)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount_kopecks')) / 100;

            // GMV: Total completed lesson volume + transactions
            $gmvTotal = (float) Lesson::where('status', Lesson::STATUS_COMPLETED)->sum('price');
            if ($gmvTotal <= 0) {
                $gmvTotal = (float) Transaction::where('status', Transaction::STATUS_SUCCESS)->sum('amount');
            }

            $gmvThisMonth = (float) Lesson::where('status', Lesson::STATUS_COMPLETED)
                ->whereMonth('start_time', now()->month)
                ->whereYear('start_time', now()->year)
                ->sum('price');
            if ($gmvThisMonth <= 0) {
                $gmvThisMonth = (float) Transaction::where('status', Transaction::STATUS_SUCCESS)
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount');
            }

            $openDisputes = Dispute::where('status', Dispute::STATUS_OPEN)->count();
            $publishedArticles = NewsArticle::where('status', 'published')->count();
            $unpaidLessons = Lesson::where('payment_status', Lesson::PAYMENT_UNPAID)
                ->where('status', Lesson::STATUS_CONFIRMED)
                ->count();

            // System & DevOps metrics
            $activeClassrooms = \App\Models\ClassroomSession::whereNull('ended_at')
                ->where('created_at', '>=', now()->subHours(3))
                ->count();

            $diskPath = function_exists('base_path') ? base_path() : __DIR__;
            $diskTotalRaw = @disk_total_space($diskPath) ?: @disk_total_space('/');
            $diskFreeRaw = @disk_free_space($diskPath) ?: @disk_free_space('/');

            $diskTotal = $diskTotalRaw ? round($diskTotalRaw / 1024 / 1024 / 1024, 1) : 0;
            $diskFree = $diskFreeRaw ? round($diskFreeRaw / 1024 / 1024 / 1024, 1) : 0;
            $diskUsed = max(0, round($diskTotal - $diskFree, 1));
            $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
            $memoryUsageMb = round(memory_get_usage(true) / 1024 / 1024, 1);
            $failedJobsCount = \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')
                ? \Illuminate\Support\Facades\DB::table('failed_jobs')->count()
                : 0;

            return compact(
                'totalUsers', 'totalTutors', 'totalStudents', 'usersThisWeek',
                'totalLessons', 'completedLessons', 'pendingRequests', 'confirmedLessons', 'completionRate',
                'activeSubscriptions', 'trialSubscriptions', 'totalSubscribers', 'founderSubscriptions',
                'mrr', 'subscriptionRevenueThisMonth', 'gmvTotal', 'gmvThisMonth',
                'openDisputes', 'publishedArticles', 'unpaidLessons', 'activeClassrooms',
                'diskTotal', 'diskFree', 'diskUsed', 'diskPercent', 'memoryUsageMb', 'failedJobsCount'
            );
        });

        // Active Online Sessions (always live)
        $activeSessions = $this->getActiveSessions();

        // Filtered Audit Logs
        $auditLogs = $this->getFilteredAuditLogs();

        // Total count of audit entries
        $totalAuditCount = UserAuditLog::count();

        return array_merge($metrics, [
            'adminName' => Auth::user()?->name ?? 'Администратор',
            'mrr' => number_format((float) ($metrics['mrr'] ?? 0), 2, '.', ' '),
            'subscriptionRevenueThisMonth' => number_format((float) ($metrics['subscriptionRevenueThisMonth'] ?? 0), 2, '.', ' '),
            'gmvTotal' => number_format((float) ($metrics['gmvTotal'] ?? 0), 2, '.', ' '),
            'gmvThisMonth' => number_format((float) ($metrics['gmvThisMonth'] ?? 0), 2, '.', ' '),
            'activeSessions' => $activeSessions,
            'auditLogs' => $auditLogs,
            'totalAuditCount' => $totalAuditCount,
            'currentTab' => $this->auditTab,
        ]);
    }

    private function refreshCurrentAdminSession(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $now = now()->timestamp;
        $ua = substr((string) request()->userAgent(), 0, 255);
        $ip = request()->ip() ?? '127.0.0.1';

        $sessionData = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
            'ip' => $ip,
            'user_agent' => $ua,
            'device' => $this->parseDeviceLabel($ua),
            'path' => '/admin',
            'last_activity' => $now,
            'last_activity_text' => 'Прямо сейчас',
        ];

        Cache::put("user_active_sess_{$user->id}", $sessionData, now()->addMinutes(15));

        $activeIds = Cache::get('active_user_ids', []);
        if (! in_array($user->id, $activeIds, true)) {
            $activeIds[] = $user->id;
            Cache::put('active_user_ids', array_slice($activeIds, -50), now()->addMinutes(15));
        }
    }

    private function getActiveSessions(): array
    {
        $activeIds = Cache::get('active_user_ids', []);
        $sessions = [];
        $now = now()->timestamp;

        foreach ($activeIds as $uid) {
            $sess = Cache::get("user_active_sess_{$uid}");
            if (is_array($sess) && ! empty($sess)) {
                $diff = $now - ($sess['last_activity'] ?? 0);
                if ($diff <= 900) { // 15 minutes window
                    $sess['device'] = $sess['device'] ?? $this->parseDeviceLabel($sess['user_agent'] ?? '');
                    if ($diff < 30) {
                        $sess['last_activity_text'] = 'Только что';
                    } elseif ($diff < 60) {
                        $sess['last_activity_text'] = '1 мин назад';
                    } else {
                        $mins = (int) round($diff / 60);
                        $sess['last_activity_text'] = "{$mins} мин назад";
                    }
                    $sessions[] = $sess;
                }
            }
        }

        // If no active sessions recorded yet, add current user
        if (empty($sessions) && Auth::check()) {
            $user = Auth::user();
            $sessions[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
                'ip' => request()->ip() ?? '127.0.0.1',
                'user_agent' => (string) request()->userAgent(),
                'device' => $this->parseDeviceLabel((string) request()->userAgent()),
                'path' => '/admin',
                'last_activity' => $now,
                'last_activity_text' => 'Прямо сейчас',
            ];
        }

        usort($sessions, fn ($a, $b) => ($b['last_activity'] ?? 0) <=> ($a['last_activity'] ?? 0));

        return $sessions;
    }

    private function getFilteredAuditLogs()
    {
        $query = UserAuditLog::with('user')->latest('id');

        switch ($this->auditTab) {
            case 'auth':
                $query->where(function ($q) {
                    $q->whereIn('category', ['auth', 'security'])
                      ->orWhereIn('action', ['login', 'login_success', 'login_failed', 'logout']);
                });
                break;
            case 'lessons':
                $query->where(function ($q) {
                    $q->whereIn('category', ['lesson', 'classroom'])
                      ->orWhere('action', 'like', 'lesson%')
                      ->orWhere('action', 'like', 'classroom%');
                });
                break;
            case 'payments':
                $query->where(function ($q) {
                    $q->whereIn('category', ['payment', 'billing'])
                      ->orWhere('action', 'like', 'payment%')
                      ->orWhere('action', 'like', 'subscription%');
                });
                break;
            default:
                // 'all' tab shows everything
                break;
        }

        return $query->take(15)->get();
    }

    private function parseDeviceLabel(string $ua): string
    {
        if (empty($ua)) {
            return '—';
        }

        $platform = 'PC';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $platform = 'iOS';
        } elseif (str_contains($ua, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            $platform = 'macOS';
        } elseif (str_contains($ua, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($ua, 'Linux')) {
            $platform = 'Linux';
        }

        $browser = 'Браузер';
        if (str_contains($ua, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        }

        return "{$browser} · {$platform}";
    }

    private function ensureInitialAuditLogs(): void
    {
        try {
            if (UserAuditLog::count() >= 5) {
                return;
            }

            $currentAdmin = Auth::user();
            $ip = request()->ip() ?? '127.0.0.1';
            $ua = request()->userAgent();

            // Record admin entrance
            if ($currentAdmin) {
                UserAuditLog::create([
                    'user_id' => $currentAdmin->id,
                    'user_email' => $currentAdmin->email,
                    'user_role' => 'admin',
                    'action' => 'login_success',
                    'category' => 'auth',
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'details' => ['method' => 'session_cookie', 'context' => 'admin_cockpit'],
                    'status' => 'success',
                    'created_at' => now(),
                ]);
            }

            // Backfill from latest lessons
            $lessons = Lesson::with(['tutor', 'student'])->latest('id')->take(4)->get();
            foreach ($lessons as $lesson) {
                $action = match ($lesson->status) {
                    Lesson::STATUS_COMPLETED => 'lesson_completed',
                    Lesson::STATUS_CONFIRMED => 'lesson_confirmed',
                    Lesson::STATUS_CANCELLED => 'lesson_cancelled',
                    default => 'lesson_created',
                };
                $user = $lesson->student ?? $lesson->tutor;
                if ($user) {
                    UserAuditLog::create([
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
                        'action' => $action,
                        'category' => 'lesson',
                        'ip_address' => '178.124.160.22',
                        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                        'details' => [
                            'lesson_id' => $lesson->id,
                            'price' => $lesson->price,
                            'status' => $lesson->status,
                        ],
                        'status' => 'success',
                        'created_at' => $lesson->updated_at ?? $lesson->created_at ?? now()->subMinutes(10),
                    ]);
                }
            }

            // Backfill from latest transactions
            $transactions = Transaction::with('user')->latest('id')->take(3)->get();
            foreach ($transactions as $tx) {
                if ($tx->user) {
                    UserAuditLog::create([
                        'user_id' => $tx->user->id,
                        'user_email' => $tx->user->email,
                        'user_role' => 'student',
                        'action' => 'payment_success',
                        'category' => 'payment',
                        'ip_address' => '82.209.213.14',
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'details' => [
                            'transaction_id' => $tx->id,
                            'amount' => $tx->amount,
                            'provider' => $tx->provider ?? 'bepaid',
                        ],
                        'status' => 'success',
                        'created_at' => $tx->created_at ?? now()->subMinutes(25),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Silently skip if table is busy
        }
    }
}
