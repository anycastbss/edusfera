<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Pages;

use App\Models\PlatformTelemetry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemTelemetryPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.site-admin.pages.system-telemetry-page';

    protected static ?string $navigationLabel = 'Телеметрия и Поведение';

    protected static ?string $title = 'Центр телеметрии, производительности и поведения';

    protected static ?string $navigationGroup = 'Мониторинг и карта системы';

    protected static ?int $navigationSort = 2;

    public string $period = '24h'; // '1h', '6h', '24h', '7d', '30d'

    public string $activeTab = 'overview'; // 'overview', 'utm', 'behavior', 'performance', 'errors', 'live'

    public string $filterRole = 'all';

    public string $filterStatus = 'all'; // 'all', 'success', 'errors', 'slow'

    public string $searchQuery = '';

    public ?int $selectedTelemetryId = null;

    public ?array $selectedRecord = null;

    // Aggregated Metrics
    public array $kpiStats = [];

    public array $hourlyTraffic = [];

    public array $deviceStats = [];

    public array $browserStats = [];

    public array $roleStats = [];

    public array $utmSources = [];

    public array $topPages = [];

    public array $slowestRoutes = [];

    public array $topSqlHeavyRoutes = [];

    public array $recentErrors = [];

    public array $liveStream = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $this->refreshData();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->refreshData();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->refreshData();
    }

    public function updatedFilterRole(): void
    {
        $this->refreshData();
    }

    public function updatedFilterStatus(): void
    {
        $this->refreshData();
    }

    public function updatedSearchQuery(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        try {
            $cutoff = $this->getPeriodCutoff();

            $baseQuery = PlatformTelemetry::query()->where('created_at', '>=', $cutoff);

            // KPI Stats
            $totalRequests = (clone $baseQuery)->count();
            $successfulRequests = (clone $baseQuery)->where('status_code', '<', 400)->count();
            $errorRequests = (clone $baseQuery)->where('status_code', '>=', 400)->count();
            $slowRequests = (clone $baseQuery)->where('duration_ms', '>=', 500)->count();

            $avgDuration = (clone $baseQuery)->avg('duration_ms') ?? 0;
            $avgDbDuration = (clone $baseQuery)->avg('db_duration_ms') ?? 0;
            $avgDbQueries = (clone $baseQuery)->avg('db_query_count') ?? 0;
            $peakMemory = (clone $baseQuery)->max('memory_mb') ?? 0;

            // 95th percentile latency estimate
            $p95Duration = (clone $baseQuery)->orderBy('duration_ms', 'desc')
                ->skip(max(0, (int) round($totalRequests * 0.05)))
                ->value('duration_ms') ?? $avgDuration;

            // Active users in last 15 min
            $activeUsersCount = PlatformTelemetry::query()
                ->where('created_at', '>=', now()->subMinutes(15))
                ->distinct('ip_address')
                ->count('ip_address');

            $activeLoggedUsersCount = PlatformTelemetry::query()
                ->where('created_at', '>=', now()->subMinutes(15))
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id');

            $this->kpiStats = [
                'total_requests' => $totalRequests,
                'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 1) : 100,
                'error_count' => $errorRequests,
                'slow_count' => $slowRequests,
                'avg_duration_ms' => round((float) $avgDuration, 1),
                'p95_duration_ms' => (int) $p95Duration,
                'avg_db_duration_ms' => round((float) $avgDbDuration, 1),
                'avg_db_queries' => round((float) $avgDbQueries, 1),
                'peak_memory_mb' => round((float) $peakMemory, 1),
                'active_users_15m' => $activeUsersCount,
                'active_logged_users_15m' => $activeLoggedUsersCount,
            ];

            // Tab-specific loading
            if ($this->activeTab === 'overview') {
                $this->loadOverviewData($cutoff);
            } elseif ($this->activeTab === 'utm') {
                $this->loadUtmData($cutoff);
            } elseif ($this->activeTab === 'behavior') {
                $this->loadBehaviorData($cutoff);
            } elseif ($this->activeTab === 'performance') {
                $this->loadPerformanceData($cutoff);
            } elseif ($this->activeTab === 'errors') {
                $this->loadErrorsData($cutoff);
            } elseif ($this->activeTab === 'live') {
                $this->loadLiveStreamData();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Предупреждение при загрузке телеметрии')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }

    private function loadUtmData(Carbon $cutoff): void
    {
        // Fetch records having UTM in query_params or beacon metadata
        $records = PlatformTelemetry::query()
            ->where('created_at', '>=', $cutoff)
            ->where(function ($q) {
                $q->where('url', 'like', '%utm_%')
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.query_params.utm_source')) IS NOT NULL")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.utm_source')) IS NOT NULL");
            })
            ->get(['id', 'url', 'metadata', 'ip_address', 'device_type', 'browser', 'created_at', 'action_type', 'user_role', 'user_id']);

        $groups = [];

        foreach ($records as $r) {
            $meta = is_array($r->metadata) ? $r->metadata : [];
            $queryParams = $meta['query_params'] ?? [];

            // Extract UTM params from query_params, metadata root, or URL
            $source = $queryParams['utm_source'] ?? $meta['utm_source'] ?? null;
            $medium = $queryParams['utm_medium'] ?? $meta['utm_medium'] ?? null;
            $campaign = $queryParams['utm_campaign'] ?? $meta['utm_campaign'] ?? null;

            if (! $source && str_contains($r->url, 'utm_source=')) {
                $query = parse_url($r->url, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $parsed);
                    $source = $parsed['utm_source'] ?? null;
                    $medium = $parsed['utm_medium'] ?? null;
                    $campaign = $parsed['utm_campaign'] ?? null;
                }
            }

            $source = $source ? (string) $source : '(direct)';
            $medium = $medium ? (string) $medium : '(none)';
            $campaign = $campaign ? (string) $campaign : '(not set)';

            $groupKey = $source.' / '.$medium.($campaign !== '(not set)' ? ' ['.$campaign.']' : '');

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'source' => $source,
                    'medium' => $medium,
                    'campaign' => $campaign,
                    'label' => $groupKey,
                    'hits' => 0,
                    'unique_ips' => [],
                    'registered_users' => 0,
                    'last_seen' => $r->created_at,
                    'devices' => [],
                ];
            }

            $groups[$groupKey]['hits']++;
            $groups[$groupKey]['unique_ips'][$r->ip_address] = true;
            if ($r->user_id) {
                $groups[$groupKey]['registered_users']++;
            }
            if ($r->created_at > $groups[$groupKey]['last_seen']) {
                $groups[$groupKey]['last_seen'] = $r->created_at;
            }

            $dev = $r->device_type ?: 'desktop';
            $groups[$groupKey]['devices'][$dev] = ($groups[$groupKey]['devices'][$dev] ?? 0) + 1;
        }

        // Format and sort
        $list = [];
        foreach ($groups as $g) {
            $g['unique_visitors'] = count($g['unique_ips']);
            unset($g['unique_ips']);
            $list[] = $g;
        }

        usort($list, fn ($a, $b) => $b['hits'] <=> $a['hits']);

        $this->utmSources = $list;
    }

    private function getPeriodCutoff(): Carbon
    {
        return match ($this->period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };
    }

    private function loadOverviewData(Carbon $cutoff): void
    {
        // 1. Hourly Traffic Breakdown
        $hourly = PlatformTelemetry::query()
            ->selectRaw('DATE_FORMAT(created_at, "%H:00") as hour_label, count(*) as count, sum(case when status_code >= 400 then 1 else 0 end) as errors, avg(duration_ms) as avg_duration')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('hour_label')
            ->orderBy('hour_label')
            ->get();

        $this->hourlyTraffic = $hourly->toArray();

        // 2. Device Breakdown
        $devices = PlatformTelemetry::query()
            ->select('device_type', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();
        $this->deviceStats = $devices->toArray();

        // 3. Browser Breakdown
        $browsers = PlatformTelemetry::query()
            ->select('browser', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(6)
            ->get();
        $this->browserStats = $browsers->toArray();

        // 4. Role Breakdown
        $roles = PlatformTelemetry::query()
            ->select('user_role', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('user_role')
            ->orderByDesc('count')
            ->get();
        $this->roleStats = $roles->toArray();
    }

    private function loadBehaviorData(Carbon $cutoff): void
    {
        // Top 15 Visited Pages
        $pages = PlatformTelemetry::query()
            ->select('url', 'route', DB::raw('count(*) as views_count'), DB::raw('count(distinct ip_address) as unique_visitors'), DB::raw('round(avg(duration_ms), 1) as avg_latency'))
            ->where('created_at', '>=', $cutoff)
            ->where('action_type', '!=', 'beacon')
            ->groupBy('url', 'route')
            ->orderByDesc('views_count')
            ->limit(15)
            ->get();

        $this->topPages = $pages->toArray();
    }

    private function loadPerformanceData(Carbon $cutoff): void
    {
        // Top 10 Slowest Routes
        $slow = PlatformTelemetry::query()
            ->select('url', 'method', DB::raw('count(*) as hits'), DB::raw('round(avg(duration_ms), 1) as avg_ms'), DB::raw('max(duration_ms) as max_ms'), DB::raw('round(avg(db_duration_ms), 1) as avg_db_ms'), DB::raw('round(avg(db_query_count), 1) as avg_queries'))
            ->where('created_at', '>=', $cutoff)
            ->where('action_type', '!=', 'beacon')
            ->groupBy('url', 'method')
            ->having('hits', '>=', 2)
            ->orderByDesc('avg_ms')
            ->limit(10)
            ->get();

        $this->slowestRoutes = $slow->toArray();

        // Top 10 SQL-heavy routes (N+1 detectors)
        $sqlHeavy = PlatformTelemetry::query()
            ->select('url', 'method', DB::raw('count(*) as hits'), DB::raw('round(avg(db_query_count), 1) as avg_queries'), DB::raw('max(db_query_count) as max_queries'), DB::raw('round(avg(db_duration_ms), 1) as avg_db_ms'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('url', 'method')
            ->orderByDesc('avg_queries')
            ->limit(10)
            ->get();

        $this->topSqlHeavyRoutes = $sqlHeavy->toArray();
    }

    private function loadErrorsData(Carbon $cutoff): void
    {
        // Recent 4xx and 5xx errors
        $errors = PlatformTelemetry::query()
            ->with('user')
            ->where('created_at', '>=', $cutoff)
            ->where('status_code', '>=', 400)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $this->recentErrors = $errors->toArray();
    }

    private function loadLiveStreamData(): void
    {
        $query = PlatformTelemetry::query()->with('user')->orderByDesc('id');

        if ($this->filterRole !== 'all') {
            $query->where('user_role', $this->filterRole);
        }

        if ($this->filterStatus === 'errors') {
            $query->where('status_code', '>=', 400);
        } elseif ($this->filterStatus === 'slow') {
            $query->where('duration_ms', '>=', 500);
        } elseif ($this->filterStatus === 'success') {
            $query->where('status_code', '<', 400);
        }

        if (! empty($this->searchQuery)) {
            $term = '%' . $this->searchQuery . '%';
            $query->where(function ($q) use ($term) {
                $q->where('url', 'like', $term)
                  ->orWhere('ip_address', 'like', $term)
                  ->orWhere('route', 'like', $term)
                  ->orWhere('event_name', 'like', $term);
            });
        }

        $this->liveStream = $query->limit(40)->get()->toArray();
    }

    public function inspectRecord(int $id): void
    {
        $this->selectedTelemetryId = $id;
        $record = PlatformTelemetry::with('user')->find($id);
        $this->selectedRecord = $record ? $record->toArray() : null;
    }

    public function closeInspector(): void
    {
        $this->selectedTelemetryId = null;
        $this->selectedRecord = null;
    }

    public function clearOldTelemetry(): void
    {
        $count = PlatformTelemetry::where('created_at', '<', now()->subDays(14))->delete();

        Notification::make()
            ->title('Журнал телеметрии очищен')
            ->body("Удалено {$count} записей старше 14 дней.")
            ->success()
            ->send();

        $this->refreshData();
    }
}
