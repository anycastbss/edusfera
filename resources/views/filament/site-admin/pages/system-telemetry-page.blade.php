<x-filament-panels::page>
    <style>
        .telemetry-root {
            font-family: inherit;
        }

        /* Top Header Card */
        .telemetry-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }
        .dark .telemetry-header {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .telemetry-header-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dark .telemetry-header-title {
            color: #f8fafc;
        }

        .telemetry-header-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .dark .telemetry-header-sub {
            color: #94a3b8;
        }

        /* Pulse Live Indicator */
        .live-dot {
            position: relative;
            display: inline-flex;
            width: 10px;
            height: 10px;
        }
        .live-dot-ping {
            position: absolute;
            display: inline-flex;
            width: 100%;
            height: 100%;
            border-radius: 9999px;
            background-color: #10b981;
            opacity: 0.75;
            animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        .live-dot-core {
            position: relative;
            display: inline-flex;
            width: 10px;
            height: 10px;
            border-radius: 9999px;
            background-color: #10b981;
        }

        /* Period Switcher */
        .period-group {
            display: inline-flex;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            gap: 2px;
        }
        .dark .period-group {
            background: #1f2937;
            border-color: #374151;
        }

        .period-btn {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 9px;
            color: #64748b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .period-btn {
            color: #9ca3af;
        }
        .period-btn:hover {
            color: #0f172a;
        }
        .dark .period-btn:hover {
            color: #ffffff;
        }
        .period-btn.active {
            background: #7c3aed;
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(124, 58, 237, 0.3);
        }

        /* KPI Metric Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .kpi-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }
        .dark .kpi-card {
            background: #111827;
            border-color: #1f2937;
        }
        .dark .kpi-card:hover {
            border-color: #374151;
        }

        .kpi-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .kpi-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .kpi-label {
            color: #94a3b8;
        }
        .kpi-icon-wrap {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .kpi-val {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
        .dark .kpi-val {
            color: #f8fafc;
        }

        .kpi-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .dark .kpi-sub {
            color: #94a3b8;
        }

        /* Navigation Tabs */
        .telemetry-tabs-nav {
            display: flex;
            gap: 6px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 2px;
        }
        .dark .telemetry-tabs-nav {
            border-bottom-color: #1f2937;
        }

        .telemetry-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 10px 10px 0 0;
            color: #64748b;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .dark .telemetry-tab-btn {
            color: #9ca3af;
        }
        .telemetry-tab-btn:hover {
            color: #0f172a;
            background: #f8fafc;
        }
        .dark .telemetry-tab-btn:hover {
            color: #ffffff;
            background: #1f2937;
        }
        .telemetry-tab-btn.active {
            color: #7c3aed !important;
            border-bottom-color: #7c3aed;
            background: #f5f3ff;
        }
        .dark .telemetry-tab-btn.active {
            color: #a78bfa !important;
            background: rgba(124, 58, 237, 0.12);
        }

        /* Card Panels */
        .content-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
        }
        .dark .content-card {
            background: #111827;
            border-color: #1f2937;
        }

        .content-card-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .content-card-title {
            color: #f8fafc;
        }

        /* Histogram Bars */
        .histogram-container {
            height: 180px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding-top: 24px;
            overflow-x: auto;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .dark .histogram-container {
            border-bottom-color: #1f2937;
        }

        .histogram-col {
            flex: 1;
            min-width: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            height: 100%;
            justify-content: flex-end;
            position: relative;
        }
        .histogram-bar {
            width: 100%;
            border-radius: 6px 6px 2px 2px;
            transition: all 0.3s ease;
            background: linear-gradient(180deg, #7c3aed 0%, #a855f7 100%);
        }
        .histogram-bar.has-errors {
            background: linear-gradient(180deg, #ef4444 0%, #f43f5e 100%);
        }
        .histogram-label {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            margin-top: 4px;
        }
        .dark .histogram-label {
            color: #94a3b8;
        }

        /* Progress items in breakdown */
        .breakdown-item {
            margin-bottom: 14px;
        }
        .breakdown-head {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .breakdown-label {
            font-weight: 600;
            color: #1e293b;
        }
        .dark .breakdown-label {
            color: #e2e8f0;
        }
        .breakdown-count {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #64748b;
        }
        .dark .breakdown-count {
            color: #94a3b8;
        }
        .breakdown-bar-bg {
            height: 6px;
            background: #f1f5f9;
            border-radius: 9999px;
            overflow: hidden;
        }
        .dark .breakdown-bar-bg {
            background: #1f2937;
        }
        .breakdown-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        /* Tables */
        .telemetry-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            font-size: 12px;
        }
        .telemetry-table th {
            padding: 10px 14px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .telemetry-table th {
            background: #1a2234;
            color: #94a3b8;
            border-bottom-color: #1f2937;
        }
        .telemetry-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .dark .telemetry-table td {
            border-bottom-color: #1f2937;
            color: #cbd5e1;
        }
        .telemetry-table tr:hover td {
            background: #f8fafc;
        }
        .dark .telemetry-table tr:hover td {
            background: #1a2234;
        }

        /* Status Badge */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .status-pill-2xx {
            background: #dcfce7;
            color: #15803d;
        }
        .dark .status-pill-2xx {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .status-pill-3xx {
            background: #e0f2fe;
            color: #0369a1;
        }
        .dark .status-pill-3xx {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
        }
        .status-pill-4xx {
            background: #fef3c7;
            color: #b45309;
        }
        .dark .status-pill-4xx {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }
        .status-pill-5xx {
            background: #fee2e2;
            color: #b91c1c;
        }
        .dark .status-pill-5xx {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        /* Action Buttons */
        .action-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .action-link-btn:hover {
            background: #7c3aed;
            color: #ffffff;
            border-color: #7c3aed;
        }
        .dark .action-link-btn {
            background: rgba(124, 58, 237, 0.15);
            color: #c4b5fd;
            border-color: rgba(124, 58, 237, 0.3);
        }
        .dark .action-link-btn:hover {
            background: #7c3aed;
            color: #ffffff;
        }
    </style>

    <div wire:poll.15s="refreshData" class="telemetry-root">

        {{-- Top Bar: Header, Live Indicator, Period Switcher --}}
        <div class="telemetry-header">
            <div class="flex items-center gap-3">
                <span class="live-dot">
                    <span class="live-dot-ping"></span>
                    <span class="live-dot-core"></span>
                </span>
                <div>
                    <div class="telemetry-header-title">
                        <span>Центр Телеметрии Edusfera</span>
                        <span class="px-2 py-0.5 text-[10px] font-mono rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-bold">
                            Live Daemon
                        </span>
                    </div>
                    <div class="telemetry-header-sub">
                        Сквозной сбор системных метрик, времени отклика, нагрузки базы данных и активности пользователей
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                {{-- Period Selector --}}
                <div class="period-group">
                    @foreach(['1h' => '1 час', '6h' => '6 часов', '24h' => '24 часа', '7d' => '7 дней', '30d' => '30 дней'] as $key => $label)
                        <button type="button" 
                                wire:click="setPeriod('{{ $key }}')" 
                                class="period-btn {{ $period === $key ? 'active' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Action: Prune --}}
                <button type="button" 
                        wire:click="clearOldTelemetry"
                        wire:confirm="Удалить записи телеметрии старше 14 дней для оптимизации хранилища?"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center gap-1.5">
                    <x-heroicon-m-trash class="w-3.5 h-3.5 text-slate-400" />
                    <span>Очистить &gt;14д</span>
                </button>
            </div>
        </div>

        {{-- KPI Cards Grid (6 Columns) --}}
        <div class="kpi-grid">
            {{-- 1. Total Requests --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">Запросы</span>
                    <div class="kpi-icon-wrap bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400">
                        <x-heroicon-m-arrow-path class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val text-purple-700 dark:text-purple-400">
                    {{ number_format($kpiStats['total_requests'] ?? 0) }}
                </div>
                <div class="kpi-sub">
                    <span>Трафик в периоде</span>
                </div>
            </div>

            {{-- 2. Success Rate --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">Надежность (SLA)</span>
                    <div class="kpi-icon-wrap bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <x-heroicon-m-shield-check class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val {{ ($kpiStats['success_rate'] ?? 100) >= 99 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ $kpiStats['success_rate'] ?? 100 }}%
                </div>
                <div class="kpi-sub">
                    <span>Ошибок:</span>
                    <span class="font-bold {{ ($kpiStats['error_count'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500' }}">
                        {{ $kpiStats['error_count'] ?? 0 }}
                    </span>
                </div>
            </div>

            {{-- 3. Latency TTFB --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">Ср. Latency</span>
                    <div class="kpi-icon-wrap bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <x-heroicon-m-bolt class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val text-amber-600 dark:text-amber-400">
                    {{ $kpiStats['avg_duration_ms'] ?? 0 }} <span class="text-xs font-normal text-slate-500">мс</span>
                </div>
                <div class="kpi-sub">
                    <span>p95 перцентиль:</span>
                    <span class="font-bold font-mono text-slate-700 dark:text-slate-300">{{ $kpiStats['p95_duration_ms'] ?? 0 }} мс</span>
                </div>
            </div>

            {{-- 4. Active Users 15m --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">Онлайн (15 мин)</span>
                    <div class="kpi-icon-wrap bg-cyan-50 text-cyan-600 dark:bg-cyan-950/40 dark:text-cyan-400">
                        <x-heroicon-m-users class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val text-cyan-600 dark:text-cyan-400">
                    {{ $kpiStats['active_users_15m'] ?? 0 }}
                </div>
                <div class="kpi-sub">
                    <span>Авторизовано:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $kpiStats['active_logged_users_15m'] ?? 0 }}</span>
                </div>
            </div>

            {{-- 5. DB Queries Load --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">База данных</span>
                    <div class="kpi-icon-wrap bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                        <x-heroicon-m-server-stack class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val text-indigo-600 dark:text-indigo-400">
                    {{ $kpiStats['avg_db_queries'] ?? 0 }} <span class="text-xs font-normal text-slate-500">SQL</span>
                </div>
                <div class="kpi-sub">
                    <span>Время SQL:</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ $kpiStats['avg_db_duration_ms'] ?? 0 }} мс</span>
                </div>
            </div>

            {{-- 6. Peak Memory --}}
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-label">Пик памяти</span>
                    <div class="kpi-icon-wrap bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <x-heroicon-m-cpu-chip class="w-4 h-4" />
                    </div>
                </div>
                <div class="kpi-val text-slate-900 dark:text-slate-100">
                    {{ $kpiStats['peak_memory_mb'] ?? 0 }} <span class="text-xs font-normal text-slate-500">МБ</span>
                </div>
                <div class="kpi-sub">
                    <span>Медленных (&gt;500мс):</span>
                    <span class="font-bold {{ ($kpiStats['slow_count'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500' }}">
                        {{ $kpiStats['slow_count'] ?? 0 }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="telemetry-tabs-nav">
            @php
                $tabs = [
                    'overview' => ['label' => 'Обзор и Нагрузка', 'icon' => 'heroicon-m-chart-bar'],
                    'utm' => ['label' => 'Трафик и UTM (Threads, Ads)', 'icon' => 'heroicon-m-arrow-trending-up'],
                    'behavior' => ['label' => 'Поведение пользователей', 'icon' => 'heroicon-m-user-group'],
                    'performance' => ['label' => 'Скорость и Узкие места', 'icon' => 'heroicon-m-bolt'],
                    'errors' => ['label' => 'Ошибки и Сбои (' . ($kpiStats['error_count'] ?? 0) . ')', 'icon' => 'heroicon-m-exclamation-triangle'],
                    'live' => ['label' => 'Живой поток запросов', 'icon' => 'heroicon-m-radio'],
                ];
            @endphp

            @foreach($tabs as $key => $tabInfo)
                <button type="button" 
                        wire:click="setTab('{{ $key }}')"
                        class="telemetry-tab-btn {{ $activeTab === $key ? 'active' : '' }}">
                    <x-dynamic-component :component="$tabInfo['icon']" class="w-4 h-4" />
                    <span>{{ $tabInfo['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB 1: OVERVIEW & LOAD                                                    --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'overview')
            <div class="space-y-6">
                {{-- 24h Traffic Histogram --}}
                <div class="content-card">
                    <div class="content-card-title">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-clock class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            <span>Распределение нагрузки по часам (24 часа)</span>
                        </div>
                        <div class="text-xs font-normal text-slate-500 flex items-center gap-3">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-purple-600 inline-block"></span> Запросы</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-rose-500 inline-block"></span> Ошибки (4xx/5xx)</span>
                        </div>
                    </div>

                    @if(empty($hourlyTraffic))
                        <div class="py-12 text-center text-slate-400 text-xs">
                            Телеметрия активна. Первые почасовые точки сформируются в течение часа.
                        </div>
                    @else
                        @php
                            $maxCount = max(array_map(fn($h) => $h['count'], $hourlyTraffic)) ?: 1;
                        @endphp
                        <div class="histogram-container">
                            @foreach($hourlyTraffic as $slot)
                                @php
                                    $heightPct = max(8, round(($slot['count'] / $maxCount) * 100));
                                    $hasErrors = ($slot['errors'] ?? 0) > 0;
                                @endphp
                                <div class="histogram-col group">
                                    {{-- Tooltip --}}
                                    <div class="absolute -top-10 hidden group-hover:flex flex-col items-center z-30 pointer-events-none">
                                        <div class="bg-slate-900 text-white text-[10px] px-2.5 py-1 rounded shadow-xl border border-slate-700 whitespace-nowrap">
                                            {{ $slot['hour_label'] }}: {{ $slot['count'] }} запр. ({{ round($slot['avg_duration']) }} мс)
                                            @if($hasErrors) | {{ $slot['errors'] }} ошибок @endif
                                        </div>
                                    </div>

                                    <div class="histogram-bar {{ $hasErrors ? 'has-errors' : '' }}" style="height: {{ $heightPct }}%;"></div>
                                    <span class="histogram-label">{{ $slot['hour_label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Breakdowns Grid: Devices, Browsers, Roles --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {{-- Devices --}}
                    <div class="content-card">
                        <div class="content-card-title">
                            <span class="flex items-center gap-1.5">
                                <x-heroicon-m-device-phone-mobile class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
                                <span>Устройства</span>
                            </span>
                        </div>
                        <div>
                            @forelse($deviceStats as $d)
                                @php
                                    $totalDev = $kpiStats['total_requests'] ?: 1;
                                    $pct = round(($d['count'] / $totalDev) * 100);
                                @endphp
                                <div class="breakdown-item">
                                    <div class="breakdown-head">
                                        <span class="breakdown-label">
                                            @if($d['device_type'] === 'mobile') 📱 Смартфоны
                                            @elseif($d['device_type'] === 'tablet') 📟 Планшеты
                                            @elseif($d['device_type'] === 'bot') 🤖 Поисковые боты
                                            @else 💻 Десктоп / ПК
                                            @endif
                                        </span>
                                        <span class="breakdown-count">{{ $d['count'] }} ({{ $pct }}%)</span>
                                    </div>
                                    <div class="breakdown-bar-bg">
                                        <div class="breakdown-bar-fill bg-cyan-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-slate-400 py-4 text-center">Нет данных</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Browsers --}}
                    <div class="content-card">
                        <div class="content-card-title">
                            <span class="flex items-center gap-1.5">
                                <x-heroicon-m-globe-alt class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                <span>Браузеры</span>
                            </span>
                        </div>
                        <div>
                            @forelse($browserStats as $b)
                                @php
                                    $totalBr = $kpiStats['total_requests'] ?: 1;
                                    $pct = round(($b['count'] / $totalBr) * 100);
                                @endphp
                                <div class="breakdown-item">
                                    <div class="breakdown-head">
                                        <span class="breakdown-label">{{ $b['browser'] }}</span>
                                        <span class="breakdown-count">{{ $b['count'] }} ({{ $pct }}%)</span>
                                    </div>
                                    <div class="breakdown-bar-bg">
                                        <div class="breakdown-bar-fill bg-purple-600" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-slate-400 py-4 text-center">Нет данных</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- User Roles --}}
                    <div class="content-card">
                        <div class="content-card-title">
                            <span class="flex items-center gap-1.5">
                                <x-heroicon-m-user-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                <span>Роли пользователей</span>
                            </span>
                        </div>
                        <div>
                            @forelse($roleStats as $r)
                                @php
                                    $totalRl = $kpiStats['total_requests'] ?: 1;
                                    $pct = round(($r['count'] / $totalRl) * 100);
                                @endphp
                                <div class="breakdown-item">
                                    <div class="breakdown-head">
                                        <span class="breakdown-label">
                                            @if($r['user_role'] === 'tutor') 👨‍🏫 Репетиторы
                                            @elseif($r['user_role'] === 'student') 🎒 Ученики
                                            @elseif($r['user_role'] === 'admin' || $r['user_role'] === 'tech-admin') 🛠️ Администраторы
                                            @else 🌐 Гости платформы
                                            @endif
                                        </span>
                                        <span class="breakdown-count">{{ $r['count'] }} ({{ $pct }}%)</span>
                                    </div>
                                    <div class="breakdown-bar-bg">
                                        <div class="breakdown-bar-fill bg-emerald-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-slate-400 py-4 text-center">Нет данных</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        {{-- ========================================================================= --}}
        {{-- TAB: UTM & CAMPAIGNS (THREADS, INSTAGRAM, ADS)                            --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'utm')
            <div class="space-y-6">
                {{-- Quick Info Card --}}
                <div class="content-card">
                    <div class="content-card-title">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-megaphone class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            <span>Источники трафика и рекламные кампании (UTM Tags)</span>
                        </div>
                        <span class="text-xs font-normal text-slate-500">Автоматическая агрегация по utm_source, utm_medium, utm_campaign</span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="telemetry-table">
                            <thead>
                                <tr>
                                    <th>Источник (Source / Medium)</th>
                                    <th>Кампания (Campaign)</th>
                                    <th class="text-right">Переходов (Hits)</th>
                                    <th class="text-right">Уникальных (IP)</th>
                                    <th class="text-right">Авторизовано</th>
                                    <th>Устройства</th>
                                    <th class="text-right">Последний визит</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($utmSources as $utm)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                @if(str_contains(strtolower($utm['source']), 'threads'))
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-black text-white dark:bg-white dark:text-black">
                                                        @ Threads
                                                    </span>
                                                @elseif(str_contains(strtolower($utm['source']), 'instagram'))
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                                                        IG
                                                    </span>
                                                @elseif(str_contains(strtolower($utm['source']), 'telegram') || str_contains(strtolower($utm['source']), 'tg'))
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-sky-500 text-white">
                                                        TG
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                        {{ $utm['source'] }}
                                                    </span>
                                                @endif
                                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $utm['source'] }}</span>
                                                <span class="text-slate-400">/</span>
                                                <span class="font-mono text-xs text-purple-600 dark:text-purple-400">{{ $utm['medium'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-mono text-xs {{ $utm['campaign'] === '(not set)' ? 'text-slate-400' : 'text-slate-700 dark:text-slate-300 font-semibold' }}">
                                                {{ $utm['campaign'] }}
                                            </span>
                                        </td>
                                        <td class="text-right font-bold text-slate-900 dark:text-slate-100 font-mono">
                                            {{ number_format($utm['hits']) }}
                                        </td>
                                        <td class="text-right font-mono text-cyan-600 dark:text-cyan-400 font-semibold">
                                            {{ number_format($utm['unique_visitors']) }}
                                        </td>
                                        <td class="text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                            {{ number_format($utm['registered_users']) }}
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                                @foreach($utm['devices'] as $dName => $dCount)
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">
                                                        {{ $dName }}: {{ $dCount }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-right font-mono text-slate-500 text-[11px]">
                                            {{ \Illuminate\Support\Carbon::parse($utm['last_seen'])->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                            <x-heroicon-m-link class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                            Визитов с UTM-метками в выбранном периоде пока не зафиксировано.
                                            <div class="mt-1 text-slate-500">
                                                Ссылка для тестирования: <code class="font-mono text-purple-600 dark:text-purple-400 font-bold">/?utm_source=threads&amp;utm_medium=bio</code>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Direct Instructions Card for Marketing --}}
                <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-900/40 bg-purple-50/50 dark:bg-purple-950/20 text-xs text-slate-700 dark:text-slate-300">
                    <div class="font-bold text-purple-900 dark:text-purple-300 mb-1 flex items-center gap-1.5">
                        <x-heroicon-m-information-circle class="w-4 h-4 text-purple-600" />
                        <span>Сквозная атрибуция и отслеживание метрик:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400">
                        <li><b>Яндекс.Метрика:</b> Подключен счетчик <code>113978262</code> с вебвизором, картой кликов и автоматической фиксацией визитов. В Метрике переходы отображаются в разделе «Отчеты → Источники → Метки UTM».</li>
                        <li><b>Внутренняя телеметрия Edusfera:</b> Метки <code>threads / bio</code> сохраняются в сессию и cookie на 30 дней. Каждое действие посетителя привязывается к источнику.</li>
                        <li><b>Тестовая ссылка:</b> <a href="https://edusfera.by/?utm_source=threads&utm_medium=bio" target="_blank" class="text-purple-600 underline font-mono">https://edusfera.by/?utm_source=threads&amp;utm_medium=bio</a></li>
                    </ul>
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 2: USER BEHAVIOR & TOP PAGES                                          --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'behavior')
            <div class="content-card">
                <div class="content-card-title">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-eye class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
                        <span>Топ-15 посещаемых страниц платформы</span>
                    </div>
                    <span class="text-xs font-normal text-slate-500">Сортировка по количеству просмотров</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="telemetry-table">
                        <thead>
                            <tr>
                                <th>URL / Страница</th>
                                <th class="text-right">Просмотров</th>
                                <th class="text-right">Уникальных IP</th>
                                <th class="text-right">Ср. отклик</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topPages as $page)
                                <tr>
                                    <td class="max-w-md truncate">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $page['url'] }}</div>
                                        @if($page['route'])
                                            <div class="text-[11px] font-mono text-purple-600 dark:text-purple-400">{{ $page['route'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold text-slate-900 dark:text-slate-100 font-mono">{{ number_format($page['views_count']) }}</td>
                                    <td class="text-right font-mono text-cyan-600 dark:text-cyan-400 font-semibold">{{ number_format($page['unique_visitors']) }}</td>
                                    <td class="text-right font-mono text-slate-600 dark:text-slate-300">{{ $page['avg_latency'] }} мс</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400">Нет данных о просмотрах</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 3: PERFORMANCE & BOTTLENECKS                                          --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'performance')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- 1. Slowest Endpoints --}}
                <div class="content-card">
                    <div class="content-card-title text-amber-600 dark:text-amber-400">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-amber-500" />
                            <span>Топ-10 медленных эндпоинтов</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="telemetry-table">
                            <thead>
                                <tr>
                                    <th>Эндпоинт</th>
                                    <th class="text-right">Ср. время</th>
                                    <th class="text-right">Макс</th>
                                    <th class="text-right">БД (мс)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowestRoutes as $sr)
                                    <tr>
                                        <td class="max-w-[200px] truncate">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $sr['method'] }}</span>
                                            <span class="font-medium text-slate-900 dark:text-slate-100 ml-1 truncate">{{ $sr['url'] }}</span>
                                        </td>
                                        <td class="text-right font-mono font-bold text-amber-600 dark:text-amber-400">{{ $sr['avg_ms'] }} мс</td>
                                        <td class="text-right font-mono text-rose-600 dark:text-rose-400">{{ $sr['max_ms'] }} мс</td>
                                        <td class="text-right font-mono text-slate-500">{{ $sr['avg_db_ms'] }} мс</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-400">Все страницы работают быстро (&lt;500 мс)</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. SQL-Heavy Endpoints --}}
                <div class="content-card">
                    <div class="content-card-title text-indigo-600 dark:text-indigo-400">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-circle-stack class="w-4 h-4 text-indigo-500" />
                            <span>Нагрузка на базу данных (Поиск N+1)</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="telemetry-table">
                            <thead>
                                <tr>
                                    <th>Эндпоинт</th>
                                    <th class="text-right">Ср. SQL</th>
                                    <th class="text-right">Макс SQL</th>
                                    <th class="text-right">Время БД</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topSqlHeavyRoutes as $sh)
                                    <tr>
                                        <td class="max-w-[200px] truncate">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $sh['method'] }}</span>
                                            <span class="font-medium text-slate-900 dark:text-slate-100 ml-1 truncate">{{ $sh['url'] }}</span>
                                        </td>
                                        <td class="text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $sh['avg_queries'] }}</td>
                                        <td class="text-right font-mono text-rose-600 dark:text-rose-400">{{ $sh['max_queries'] }}</td>
                                        <td class="text-right font-mono text-slate-500">{{ $sh['avg_db_ms'] }} мс</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-400">Запросы к базе оптимальны</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 4: ERRORS & INCIDENTS                                                 --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'errors')
            <div class="content-card">
                <div class="content-card-title text-rose-600 dark:text-rose-400">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-exclamation-triangle class="w-4 h-4 text-rose-500" />
                        <span>Журнал ошибок и сбоев (4xx, 5xx, Client JS Errors)</span>
                    </div>
                    <span class="text-xs font-normal text-slate-500">Последние 30 инцидентов</span>
                </div>

                <div class="space-y-3">
                    @forelse($recentErrors as $err)
                        <div class="p-3.5 rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="status-pill {{ $err['status_code'] >= 500 ? 'status-pill-5xx' : 'status-pill-4xx' }}">
                                        {{ $err['status_code'] }}
                                    </span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $err['method'] }}</span>
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $err['url'] }}</span>
                                </div>
                                @if($err['error_message'])
                                    <div class="text-rose-700 dark:text-rose-300 font-mono text-[11px] bg-white dark:bg-slate-900 p-2 rounded border border-rose-200 dark:border-rose-900/50">
                                        {{ $err['error_message'] }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-slate-500 flex flex-wrap items-center gap-3">
                                    <span>IP: <code class="text-cyan-700 dark:text-cyan-400 font-bold">{{ $err['ip_address'] }}</code></span>
                                    <span>Устройство: {{ $err['device_type'] }} ({{ $err['browser'] }})</span>
                                    @if($err['user'])
                                        <span>Пользователь: <b>{{ $err['user']['name'] }}</b> ({{ $err['user']['email'] }})</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-[11px] text-slate-400 whitespace-nowrap">
                                <div>{{ \Illuminate\Support\Carbon::parse($err['created_at'])->format('d.m.Y H:i:s') }}</div>
                                <button type="button" 
                                        wire:click="inspectRecord({{ $err['id'] }})"
                                        class="action-link-btn mt-1.5">
                                    Инспектор &rarr;
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 text-xs">
                            <x-heroicon-m-check-badge class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            Все запросы выполняются штатно. Сбоев и ошибок не обнаружено!
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- TAB 5: LIVE STREAM                                                        --}}
        {{-- ========================================================================= --}}
        @if($activeTab === 'live')
            <div class="content-card">
                {{-- Controls Bar --}}
                <div class="flex flex-col md:flex-row gap-3 items-center justify-between mb-4">
                    <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                        <select wire:model.live="filterStatus" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs rounded-xl px-3 py-1.5 text-slate-700 dark:text-slate-200 font-medium">
                            <option value="all">Все статусы</option>
                            <option value="success">Только успешные (2xx/3xx)</option>
                            <option value="errors">Только ошибки (4xx/5xx)</option>
                            <option value="slow">Только медленные (&gt; 500мс)</option>
                        </select>

                        <select wire:model.live="filterRole" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs rounded-xl px-3 py-1.5 text-slate-700 dark:text-slate-200 font-medium">
                            <option value="all">Все роли</option>
                            <option value="tutor">Репетиторы</option>
                            <option value="student">Ученики</option>
                            <option value="guest">Гости</option>
                            <option value="admin">Администраторы</option>
                        </select>
                    </div>

                    <div class="w-full md:w-72">
                        <input type="text" 
                               wire:model.live.debounce.300ms="searchQuery" 
                               placeholder="Поиск по URL, IP или действию..." 
                               class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs rounded-xl px-3 py-1.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-purple-600">
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="telemetry-table">
                        <thead>
                            <tr>
                                <th>Статус</th>
                                <th>Метод / URL</th>
                                <th>Пользователь</th>
                                <th>Отклик</th>
                                <th>SQL</th>
                                <th>Устройство / IP</th>
                                <th class="text-right">Время</th>
                                <th class="text-center">Инспектор</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($liveStream as $req)
                                <tr>
                                    <td>
                                        <span class="status-pill {{ $req['status_code'] >= 500 ? 'status-pill-5xx' : ($req['status_code'] >= 400 ? 'status-pill-4xx' : ($req['status_code'] >= 300 ? 'status-pill-3xx' : 'status-pill-2xx')) }}">
                                            {{ $req['status_code'] }}
                                        </span>
                                    </td>
                                    <td class="max-w-[280px] truncate">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">{{ $req['method'] }}</span>
                                        <span class="font-medium text-slate-900 dark:text-slate-100 ml-1 truncate">{{ $req['url'] }}</span>
                                    </td>
                                    <td>
                                        @if($req['user'])
                                            <span class="font-semibold text-purple-700 dark:text-purple-300 block truncate">{{ $req['user']['name'] }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono">{{ $req['user_role'] }}</span>
                                        @else
                                            <span class="text-slate-500 capitalize">{{ $req['user_role'] }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono {{ $req['duration_ms'] >= 500 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $req['duration_ms'] }} мс
                                    </td>
                                    <td class="font-mono text-slate-600 dark:text-slate-400 text-[11px]">
                                        {{ $req['db_query_count'] }} <span class="text-slate-400">({{ $req['db_duration_ms'] }}мс)</span>
                                    </td>
                                    <td class="text-[11px] text-slate-600 dark:text-slate-400">
                                        <div>{{ $req['device_type'] }} · {{ $req['browser'] }}</div>
                                        <div class="font-mono text-slate-400 text-[10px]">{{ $req['ip_address'] }}</div>
                                    </td>
                                    <td class="text-right font-mono text-[11px] text-slate-500">
                                        {{ \Illuminate\Support\Carbon::parse($req['created_at'])->format('H:i:s') }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                wire:click="inspectRecord({{ $req['id'] }})"
                                                class="action-link-btn">
                                            Детали
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400">
                                        Запросов не найдено
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ========================================================================= --}}
        {{-- MODAL: INSPECTOR                                                          --}}
        {{-- ========================================================================= --}}
        @if($selectedRecord)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="status-pill {{ $selectedRecord['status_code'] >= 500 ? 'status-pill-5xx' : 'status-pill-2xx' }}">
                                {{ $selectedRecord['status_code'] }}
                            </span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Инспектор запроса #{{ $selectedRecord['id'] }}</h3>
                        </div>
                        <button type="button" wire:click="closeInspector" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold">&times;</button>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-500 text-[11px] block">URL:</span>
                            <span class="text-slate-900 dark:text-slate-100 font-semibold break-all">{{ $selectedRecord['url'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">HTTP Метод:</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $selectedRecord['method'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">Время отклика:</span>
                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ $selectedRecord['duration_ms'] }} мс</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">База данных:</span>
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $selectedRecord['db_query_count'] }} запросов ({{ $selectedRecord['db_duration_ms'] }} мс)</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">Память:</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $selectedRecord['memory_mb'] }} МБ</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">Клиент / IP:</span>
                            <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400">{{ $selectedRecord['ip_address'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">Устройство и ОС:</span>
                            <span class="text-slate-800 dark:text-slate-200">{{ $selectedRecord['device_type'] }} · {{ $selectedRecord['os'] }} ({{ $selectedRecord['browser'] }})</span>
                        </div>
                        <div>
                            <span class="text-slate-500 text-[11px] block">Пользователь:</span>
                            <span class="font-semibold text-purple-700 dark:text-purple-300">
                                @if($selectedRecord['user'])
                                    {{ $selectedRecord['user']['name'] }} ({{ $selectedRecord['user_role'] }})
                                @else
                                    Гость ({{ $selectedRecord['user_role'] }})
                                @endif
                            </span>
                        </div>
                    </div>

                    @if($selectedRecord['error_message'])
                        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/40 p-3 rounded-xl">
                            <span class="text-[11px] font-bold text-rose-700 dark:text-rose-400 block mb-1">Сообщение об ошибке:</span>
                            <div class="text-xs text-rose-800 dark:text-rose-300 font-mono">{{ $selectedRecord['error_message'] }}</div>
                        </div>
                    @endif

                    @if(!empty($selectedRecord['user_agent']))
                        <div>
                            <span class="text-slate-500 text-[11px] block mb-1">User-Agent:</span>
                            <div class="text-[11px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-950 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 break-all font-mono">
                                {{ $selectedRecord['user_agent'] }}
                            </div>
                        </div>
                    @endif

                    @if(!empty($selectedRecord['metadata']))
                        <div>
                            <span class="text-slate-500 text-[11px] block mb-1">Метаданные (JSON):</span>
                            <pre class="text-[10px] text-slate-800 dark:text-emerald-400 bg-slate-50 dark:bg-slate-950 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 overflow-x-auto font-mono max-h-48">{{ json_encode($selectedRecord['metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="button" wire:click="closeInspector" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-white text-xs font-bold rounded-xl transition">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
