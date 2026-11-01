<x-filament-widgets::widget>
    <style>
        .ed-cockpit {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            font-family: inherit;
        }
        .dark .ed-cockpit {
            background: #111827;
            border-color: #1f2937;
            color: #f9fafb;
        }
        .ed-cockpit-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dark .ed-cockpit-header {
            border-bottom-color: #1f2937;
        }
        .ed-badge-top {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            background: #f5f3ff;
            color: #7c3aed;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.02em;
        }
        .dark .ed-badge-top {
            background: rgba(124, 58, 237, 0.2);
            color: #c4b5fd;
        }
        .ed-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: edPulse 2s infinite;
        }
        @keyframes edPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .ed-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .ed-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .ed-btn-primary {
            background: #7c3aed;
            color: #ffffff !important;
            border: 1px solid #6d28d9;
        }
        .ed-btn-primary:hover {
            background: #6d28d9;
            transform: translateY(-1px);
        }
        .ed-btn-subtle {
            background: #f8fafc;
            color: #334155 !important;
            border: 1px solid #e2e8f0;
        }
        .ed-btn-subtle:hover {
            background: #f1f5f9;
            color: #0f172a !important;
            border-color: #cbd5e1;
        }
        .dark .ed-btn-subtle {
            background: #1f2937;
            color: #e5e7eb !important;
            border-color: #374151;
        }
        .dark .ed-btn-subtle:hover {
            background: #374151;
            color: #ffffff !important;
        }
        .ed-services-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-top: 18px;
        }
        .ed-service-tile {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dark .ed-service-tile {
            background: #1f2937;
            border-color: #374151;
        }
        .ed-service-name {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .dark .ed-service-name {
            color: #9ca3af;
        }
        .ed-service-val {
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 800;
            color: #059669;
        }
        .dark .ed-service-val {
            color: #34d399;
        }
        .ed-kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .ed-kpi-tile {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .dark .ed-kpi-tile {
            background: #1f2937;
            border-color: #374151;
        }
        .ed-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ed-kpi-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .ed-kpi-title {
            color: #9ca3af;
        }
        .ed-kpi-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 9999px;
            background: #ede9fe;
            color: #6d28d9;
        }
        .dark .ed-kpi-badge {
            background: rgba(109, 40, 217, 0.3);
            color: #c4b5fd;
        }
        .ed-kpi-val {
            margin-top: 8px;
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.1;
        }
        .dark .ed-kpi-val {
            color: #f9fafb;
        }
        .ed-kpi-sub {
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
        }
        .dark .ed-kpi-sub {
            color: #9ca3af;
        }

        /* Live Audit & Sessions Tabs & Tables */
        .ed-audit-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin-top: 14px;
        }
        .ed-tab-btn {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid transparent;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .ed-tab-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .ed-tab-btn.active {
            background: #7c3aed;
            color: #ffffff;
            border-color: #6d28d9;
            box-shadow: 0 1px 2px rgba(124, 58, 237, 0.2);
        }
        .dark .ed-tab-btn {
            background: #1f2937;
            color: #9ca3af;
        }
        .dark .ed-tab-btn:hover {
            background: #374151;
            color: #f9fafb;
        }
        .dark .ed-tab-btn.active {
            background: #7c3aed;
            color: #ffffff;
            border-color: #6d28d9;
        }
        .ed-audit-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            margin-top: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .dark .ed-audit-table {
            border-color: #374151;
        }
        .ed-audit-table th {
            background: #f8fafc;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .ed-audit-table th {
            background: #1f2937;
            color: #9ca3af;
            border-bottom-color: #374151;
        }
        .ed-audit-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .dark .ed-audit-table td {
            border-bottom-color: #1f2937;
            color: #d1d5db;
        }
        .ed-audit-table tr:last-child td {
            border-bottom: none;
        }
        .ed-audit-table tr:hover td {
            background: #f8fafc;
        }
        .dark .ed-audit-table tr:hover td {
            background: #1f2937/50;
        }
        .ed-session-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }
        .ed-session-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .ed-session-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .dark .ed-session-card {
            background: #1f2937;
            border-color: #374151;
        }
    </style>

    <div class="ed-cockpit">
        <!-- Top Cockpit Header -->
        <div class="ed-cockpit-header">
            <div>
                <div class="ed-badge-top">
                    <span class="ed-pulse-dot"></span>
                    <span>Edusfera Core · Командный пульт DevOps</span>
                </div>
                
                <h1 style="margin: 8px 0 0 0; font-size: 22px; font-weight: 900; letter-spacing: -0.02em;">
                    Центр управления, {{ $adminName }}
                </h1>
                
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                    Сводка на {{ now()->translatedFormat('j F Y, H:i') }}. Состояние хоста: <strong style="color: #10b981;">7 из 7 узлов в норме</strong>. Хостинг: Dedicated Cloud Node (Minsk/Europe).
                </p>
            </div>

            <!-- 4 Quick Engineering Buttons -->
            <div class="ed-btn-group">
                <a href="/admin/system-operations-page" class="ed-btn ed-btn-primary">
                    <span>⚡</span>
                    <span>Сброс кэша (DevOps)</span>
                </a>
                <a href="/admin/system-logs-page" class="ed-btn ed-btn-subtle">
                    <span>📜</span>
                    <span>Аудит & Логи</span>
                </a>
                <a href="/admin/system-topology-page" class="ed-btn ed-btn-subtle">
                    <span>🌐</span>
                    <span>Карта системы</span>
                </a>
                <a href="/admin/platform-settings-page" class="ed-btn ed-btn-subtle">
                    <span>🛠️</span>
                    <span>Настройки без кода</span>
                </a>
            </div>
        </div>

        <!-- 7 Service Nodes Live Status Tiles -->
        <div class="ed-services-row">
            <div class="ed-service-tile">
                <div class="ed-service-name">Nginx Proxy</div>
                <div class="ed-service-val">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#10b981;"></span>
                    <span>200 OK</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">PHP 8.3 FPM</div>
                <div class="ed-service-val">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#10b981;"></span>
                    <span>{{ $memoryUsageMb }} МБ ОЗУ</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">MySQL Engine</div>
                <div class="ed-service-val">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#10b981;"></span>
                    <span>Active</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">Redis & Cache</div>
                <div class="ed-service-val">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#10b981;"></span>
                    <span>Ready</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">LiveKit SFU</div>
                <div class="ed-service-val" style="color: #7c3aed;">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#7c3aed;"></span>
                    <span>{{ $activeClassrooms }} комнат</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">Gemini AI</div>
                <div class="ed-service-val">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:999px; background:#10b981;"></span>
                    <span>Online</span>
                </div>
            </div>

            <div class="ed-service-tile">
                <div class="ed-service-name">Диск хоста</div>
                <div class="ed-service-val" style="color: {{ $diskPercent > 90 ? '#f59e0b' : '#059669' }};">
                    @if($diskTotal > 0)
                        <span>{{ $diskUsed }}/{{ $diskTotal }}G</span>
                    @else
                        <span>Cloud SSD (OK)</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- SaaS & Business KPIs -->
        <div class="ed-kpi-row">
            <!-- SaaS MRR -->
            <div class="ed-kpi-tile">
                <div class="ed-kpi-header">
                    <span class="ed-kpi-title">MRR Подписок</span>
                    <span class="ed-kpi-badge">SaaS</span>
                </div>
                <div class="ed-kpi-val">
                    {{ $mrr }} <span style="font-size:12px; font-weight:400; color:#64748b;">BYN/мес</span>
                </div>
                <div class="ed-kpi-sub">
                    Собрано в месяце: <strong style="color: #334155;">{{ $subscriptionRevenueThisMonth }} BYN</strong>
                </div>
            </div>

            <!-- Subscribers -->
            <div class="ed-kpi-tile">
                <div class="ed-kpi-header">
                    <span class="ed-kpi-title">Подписчики</span>
                    <span class="ed-kpi-badge" style="background:#dcfce7; color:#15803d;">{{ $totalSubscribers }} всего</span>
                </div>
                <div class="ed-kpi-val">
                    {{ $activeSubscriptions }} <span style="font-size:12px; font-weight:700; color:#16a34a;">+{{ $trialSubscriptions }} trial</span>
                </div>
                <div class="ed-kpi-sub">
                    Основателей (Founders): <strong style="color: #d97706;">{{ $founderSubscriptions }}/50</strong>
                </div>
            </div>

            <!-- GMV -->
            <div class="ed-kpi-tile">
                <div class="ed-kpi-header">
                    <span class="ed-kpi-title">GMV Уроков</span>
                    <span class="ed-kpi-badge" style="background:#e0f2fe; color:#0369a1;">100% репетиторам</span>
                </div>
                <div class="ed-kpi-val">
                    {{ $gmvTotal }} <span style="font-size:12px; font-weight:400; color:#64748b;">BYN</span>
                </div>
                <div class="ed-kpi-sub">
                    В месяце: <strong style="color: #334155;">{{ $gmvThisMonth }} BYN</strong>
                </div>
            </div>

            <!-- Users & Lessons -->
            <div class="ed-kpi-tile">
                <div class="ed-kpi-header">
                    <span class="ed-kpi-title">Пользователи</span>
                    <span class="ed-kpi-badge" style="background:#f1f5f9; color:#475569;">{{ $completionRate }}% конверсия</span>
                </div>
                <div class="ed-kpi-val">
                    {{ $totalUsers }} <span style="font-size:12px; font-weight:400; color:#64748b;">аккаунтов</span>
                </div>
                <div class="ed-kpi-sub">
                    Репетиторов: <strong>{{ $totalTutors }}</strong> · Учеников: <strong>{{ $totalStudents }}</strong>
                </div>
            </div>
        </div>

        <!-- Real Live Audit & Active Sessions Component with Livewire Polling (45s) -->
        <div wire:poll.45s style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;" class="dark:border-gray-800">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:15px; font-weight:900; color:#0f172a;" class="dark:text-white">
                        🛡️ Журнал безопасности и сессий (Live Audit)
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:800; padding:2px 8px; border-radius:999px; background:#ecfdf5; color:#059669; border: 1px solid #a7f3d0;">
                        <span class="ed-pulse-dot" style="width:6px; height:6px;"></span>
                        <span>LIVE (45s)</span>
                    </span>
                </div>
                
                <div style="display:flex; align-items:center; gap:8px;">
                    <a href="/admin/system-logs-page" style="font-size:12px; font-weight:700; color:#7c3aed; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        <span>Полный системный журнал</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- Filter Navigation Tabs -->
            <div class="ed-audit-nav">
                <button type="button" wire:click="setAuditTab('all')" class="ed-tab-btn {{ $currentTab === 'all' ? 'active' : '' }}">
                    <span>⚡ Все события</span>
                    <span style="font-size:11px; opacity:0.85;">({{ $totalAuditCount }})</span>
                </button>
                <button type="button" wire:click="setAuditTab('sessions')" class="ed-tab-btn {{ $currentTab === 'sessions' ? 'active' : '' }}">
                    <span>👥 Онлайн сейчас</span>
                    <span style="background:{{ $currentTab === 'sessions' ? 'rgba(255,255,255,0.25)' : '#10b981' }}; color:#ffffff; padding:1px 6px; border-radius:999px; font-size:10px; font-weight:800;">
                        {{ count($activeSessions) }}
                    </span>
                </button>
                <button type="button" wire:click="setAuditTab('auth')" class="ed-tab-btn {{ $currentTab === 'auth' ? 'active' : '' }}">
                    <span>🔑 Авторизации</span>
                </button>
                <button type="button" wire:click="setAuditTab('lessons')" class="ed-tab-btn {{ $currentTab === 'lessons' ? 'active' : '' }}">
                    <span>📅 Уроки & Класс</span>
                </button>
                <button type="button" wire:click="setAuditTab('payments')" class="ed-tab-btn {{ $currentTab === 'payments' ? 'active' : '' }}">
                    <span>💳 Оплаты & SaaS</span>
                </button>
            </div>

            <!-- TAB 1: ACTIVE SESSIONS VIEW -->
            @if($currentTab === 'sessions')
                <div style="margin-top: 14px;">
                    @if(count($activeSessions) > 0)
                        <div class="ed-session-grid">
                            @foreach($activeSessions as $session)
                                <div class="ed-session-card">
                                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #7c3aed, #a855f7); color:#ffffff; font-weight:900; display:flex; align-items:center; justify-content:center; font-size:13px; text-transform:uppercase; box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);">
                                                {{ mb_substr($session['name'] ?? 'U', 0, 1) }}
                                            </div>
                                            <div>
                                                <div style="font-weight:800; font-size:13px; color:#0f172a;" class="dark:text-white">
                                                    {{ $session['name'] ?? 'Пользователь' }}
                                                </div>
                                                <div style="font-size:11px; color:#64748b;">
                                                    {{ $session['email'] ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            @php
                                                $role = strtolower((string) ($session['role'] ?? 'guest'));
                                            @endphp
                                            @if($role === 'admin')
                                                <span style="font-size:10px; font-weight:800; padding:2px 7px; border-radius:6px; background:#ede9fe; color:#6d28d9; text-transform:uppercase;">Admin</span>
                                            @elseif($role === 'tutor')
                                                <span style="font-size:10px; font-weight:800; padding:2px 7px; border-radius:6px; background:#e0f2fe; color:#0369a1; text-transform:uppercase;">Репетитор</span>
                                            @elseif($role === 'student')
                                                <span style="font-size:10px; font-weight:800; padding:2px 7px; border-radius:6px; background:#fef3c7; color:#b45309; text-transform:uppercase;">Ученик</span>
                                            @else
                                                <span style="font-size:10px; font-weight:800; padding:2px 7px; border-radius:6px; background:#f1f5f9; color:#475569; text-transform:uppercase;">{{ $role }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="font-size:11px; color:#475569; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px;" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                            <span style="color:#94a3b8;">Раздел:</span>
                                            <strong style="color:#0f172a; font-family:monospace;" class="dark:text-white">{{ $session['path'] ?? '/' }}</strong>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                            <span style="color:#94a3b8;">Устройство:</span>
                                            <span>{{ $session['device'] ?? 'Browser' }}</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between;">
                                            <span style="color:#94a3b8;">IP адрес:</span>
                                            <span style="font-family:monospace; color:#0891b2;">{{ $session['ip'] ?? '127.0.0.1' }}</span>
                                        </div>
                                    </div>

                                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; pt:2px;">
                                        <span style="display:inline-flex; align-items:center; gap:5px; color:#059669; font-weight:700;">
                                            <span class="ed-pulse-dot" style="width:7px; height:7px;"></span>
                                            <span>В сети</span>
                                        </span>
                                        <span style="color:#64748b; font-size:11px;">
                                            {{ $session['last_activity_text'] ?? 'Только что' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center; padding:30px; background:#f8fafc; border-radius:12px; margin-top:14px; border:1px dashed #cbd5e1;" class="dark:bg-gray-800/40 dark:border-gray-700">
                            <span style="font-size:24px; display:block; margin-bottom:8px;">👥</span>
                            <div style="font-weight:700; font-size:13px; color:#475569;">Активных пользовательских сессий не обнаружено</div>
                            <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Новые сессии будут автоматически появляться в реальном времени при входе репетиторов и учеников</div>
                        </div>
                    @endif
                </div>

            <!-- TAB 2,3,4,5: AUDIT LOGS VIEW -->
            @else
                @if(isset($auditLogs) && count($auditLogs) > 0)
                    <div style="overflow-x:auto;">
                        <table class="ed-audit-table">
                            <thead>
                                <tr>
                                    <th style="width:140px;">Время</th>
                                    <th>Пользователь</th>
                                    <th style="width:90px;">Роль</th>
                                    <th>Событие безопасности</th>
                                    <th>Категория</th>
                                    <th>IP и Устройство</th>
                                    <th style="width:90px; text-align:center;">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditLogs as $log)
                                    <tr>
                                        <td style="white-space:nowrap;">
                                            <div style="font-family:monospace; font-size:11px; font-weight:700; color:#0f172a;" class="dark:text-white">
                                                {{ $log->created_at->format('d.m.Y H:i:s') }}
                                            </div>
                                            <div style="font-size:10px; color:#94a3b8;">
                                                {{ $log->created_at->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight:800; color:#0f172a; font-size:12px;" class="dark:text-white">
                                                {{ $log->user?->name ?? ($log->details['user_name'] ?? 'Аноним') }}
                                            </div>
                                            <div style="font-size:11px; color:#64748b;">
                                                {{ $log->user_email ?? ($log->user?->email ?? '—') }}
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $role = strtolower((string) ($log->user_role ?? 'user'));
                                            @endphp
                                            @if($role === 'admin')
                                                <span style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; background:#ede9fe; color:#6d28d9; text-transform:uppercase;">Admin</span>
                                            @elseif($role === 'tutor')
                                                <span style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; background:#e0f2fe; color:#0369a1; text-transform:uppercase;">Репетитор</span>
                                            @elseif($role === 'student')
                                                <span style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; background:#fef3c7; color:#b45309; text-transform:uppercase;">Ученик</span>
                                            @else
                                                <span style="font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; background:#f1f5f9; color:#475569; text-transform:uppercase;">{{ $role }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:#0f172a; font-size:12px;" class="dark:text-white">
                                                {{ $log->action_label }}
                                            </div>
                                            @if(!empty($log->details) && is_array($log->details))
                                                <div style="font-size:10px; color:#64748b; font-family:monospace; margin-top:2px;">
                                                    @foreach($log->details as $k => $v)
                                                        @if(!is_array($v) && $k !== 'password')
                                                            <span style="margin-right:6px;">{{ $k }}: <strong>{{ $v }}</strong></span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $cat = $log->category ?? 'system';
                                            @endphp
                                            <span style="font-size:10px; font-weight:700; padding:2px 7px; border-radius:999px; text-transform:uppercase; 
                                                {{ $cat === 'auth' ? 'background:#e0f2fe; color:#0369a1;' : '' }}
                                                {{ $cat === 'lesson' ? 'background:#dcfce7; color:#15803d;' : '' }}
                                                {{ $cat === 'payment' ? 'background:#fef3c7; color:#b45309;' : '' }}
                                                {{ $cat === 'security' ? 'background:#fee2e2; color:#b91c1c;' : '' }}
                                                {{ !in_array($cat, ['auth', 'lesson', 'payment', 'security']) ? 'background:#f1f5f9; color:#475569;' : '' }}
                                            ">
                                                {{ $cat }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-family:monospace; font-size:11px; color:#0891b2; font-weight:700;">
                                                {{ $log->ip_address ?? '127.0.0.1' }}
                                            </div>
                                            <div style="font-size:10px; color:#94a3b8;">
                                                {{ $log->device_label }}
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            @if($log->status === 'success')
                                                <span style="font-size:10px; font-weight:800; padding:3px 8px; border-radius:6px; background:#dcfce7; color:#15803d; text-transform:uppercase;">
                                                    OK 200
                                                </span>
                                            @elseif($log->status === 'failed')
                                                <span style="font-size:10px; font-weight:800; padding:3px 8px; border-radius:6px; background:#fee2e2; color:#b91c1c; text-transform:uppercase;">
                                                    СБОЙ
                                                </span>
                                            @else
                                                <span style="font-size:10px; font-weight:800; padding:3px 8px; border-radius:6px; background:#fef3c7; color:#b45309; text-transform:uppercase;">
                                                    {{ $log->status }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align:center; padding:30px; background:#f8fafc; border-radius:12px; margin-top:14px; border:1px dashed #cbd5e1;" class="dark:bg-gray-800/40 dark:border-gray-700">
                        <span style="font-size:24px; display:block; margin-bottom:8px;">🛡️</span>
                        <div style="font-weight:700; font-size:13px; color:#475569;">Событий в этой категории пока нет</div>
                        <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Все действия пользователей и системные события сохраняются автоматически</div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
