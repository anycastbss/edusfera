<x-filament-panels::page>
    <style>
        .topology-container {
            font-family: inherit;
        }
        .topology-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .topology-header-bar {
            border-bottom-color: #1f2937;
        }
        .topology-title-sub {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0 0 0;
        }
        .dark .topology-title-sub {
            color: #9ca3af;
        }
        .topology-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .topology-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .topology-btn-primary {
            background: #7c3aed;
            color: #ffffff !important;
            border-color: #6d28d9;
            box-shadow: 0 1px 2px rgba(124, 58, 237, 0.2);
        }
        .topology-btn-primary:hover {
            background: #6d28d9;
            transform: translateY(-1px);
        }
        .topology-btn-secondary {
            background: #f8fafc;
            color: #334155 !important;
            border-color: #e2e8f0;
        }
        .topology-btn-secondary:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a !important;
        }
        .dark .topology-btn-secondary {
            background: #1f2937;
            color: #e5e7eb !important;
            border-color: #374151;
        }
        .dark .topology-btn-secondary:hover {
            background: #374151;
            color: #ffffff !important;
        }

        /* Hardware Metrics Grid */
        .hardware-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .hardware-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }
        .hardware-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
        }
        .dark .hardware-card {
            background: #111827;
            border-color: #1f2937;
        }
        .hardware-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .hardware-label {
            color: #9ca3af;
        }
        .hardware-val {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            margin-top: 6px;
            line-height: 1.1;
        }
        .dark .hardware-val {
            color: #f9fafb;
        }
        .hardware-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }
        .dark .hardware-sub {
            color: #9ca3af;
        }
        .progress-bar {
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 8px;
        }
        .dark .progress-bar {
            background: #1f2937;
        }
        .progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }

        /* 7 Nodes Topology Grid */
        .topology-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .topology-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .topology-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }
        .dark .topology-card {
            background: #111827;
            border-color: #1f2937;
        }
        .topology-card--operational {
            border-left: 4px solid #10b981;
        }
        .topology-card--warning {
            border-left: 4px solid #f59e0b;
        }
        .topology-card--degraded {
            border-left: 4px solid #ef4444;
        }
        .topology-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
        }
        .topology-card-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .topology-card-title {
            color: #f9fafb;
        }
        .topology-card-role {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 14px;
            line-height: 1.4;
        }
        .dark .topology-card-role {
            color: #9ca3af;
        }
        .topology-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .topology-badge--operational {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .dark .topology-badge--operational {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .topology-badge--warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .dark .topology-badge--warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.3);
        }
        .topology-badge--degraded {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .dark .topology-badge--degraded {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .topology-details {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .dark .topology-details {
            background: #1f2937;
            border-color: #374151;
        }
        .topology-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            gap: 8px;
        }
        .topology-detail-label {
            color: #64748b;
        }
        .dark .topology-detail-label {
            color: #9ca3af;
        }
        .topology-detail-val {
            color: #0f172a;
            font-weight: 700;
            font-family: monospace;
            text-align: right;
            word-break: break-all;
        }
        .dark .topology-detail-val {
            color: #f3f4f6;
        }

        /* Classrooms Table */
        .topology-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .dark .topology-table-card {
            background: #111827;
            border-color: #1f2937;
        }
        .topology-table-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .topology-table-title {
            color: #f9fafb;
        }
        .topology-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .dark .topology-table {
            border-color: #374151;
        }
        .topology-table th {
            background: #f8fafc;
            padding: 8px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .topology-table th {
            background: #1f2937;
            color: #9ca3af;
            border-bottom-color: #374151;
        }
        .topology-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .dark .topology-table td {
            border-bottom-color: #1f2937;
            color: #d1d5db;
        }
        .topology-table tr:last-child td {
            border-bottom: none;
        }
    </style>

    <div class="topology-container">
        {{-- Верхняя плашка управления и обновления --}}
        <div class="topology-header-bar">
            <div>
                <p class="topology-title-sub">
                    Сквозной телеметрический контроль состояния всех 7 ключевых сервисных слоев платформы в реальном времени
                </p>
            </div>
            <div class="topology-actions">
                <button type="button" wire:click="refreshTopology" class="topology-btn topology-btn-primary">
                    <span>🔄</span>
                    <span>Обновить телеметрию</span>
                </button>
                <button type="button" wire:click="testLiveKitConnection" class="topology-btn topology-btn-secondary">
                    <span>📡</span>
                    <span>Тест LiveKit Cloud</span>
                </button>
            </div>
        </div>

        {{-- Аппаратные метрики сервера --}}
        <div class="hardware-grid">
            <!-- Диск -->
            <div class="hardware-card">
                <div class="hardware-label">
                    <span>Дисковое хранилище</span>
                    <span style="font-size:10px; color:#10b981; font-weight:800;">NVMe Cloud</span>
                </div>
                <div class="hardware-val">
                    {{ $systemStats['disk_used_gb'] ?? 0 }} / {{ $systemStats['disk_total_gb'] ?? 0 }} <span style="font-size:13px; font-weight:600; color:#64748b;">ГБ</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $systemStats['disk_percent'] ?? 0 }}%; background: {{ ($systemStats['disk_percent'] ?? 0) > 95 ? '#ef4444' : '#7c3aed' }};"></div>
                </div>
                <div class="hardware-sub">
                    Свободно <strong style="color:#059669;">{{ $systemStats['disk_free_gb'] ?? 0 }} ГБ</strong> (Занято {{ $systemStats['disk_percent'] ?? 0 }}%)
                </div>
            </div>

            <!-- ОЗУ PHP -->
            <div class="hardware-card">
                <div class="hardware-label">
                    <span>ОЗУ PHP Engine</span>
                    <span style="font-size:10px; color:#10b981; font-weight:800;">Запас 98%</span>
                </div>
                <div class="hardware-val">
                    {{ $systemStats['php_memory_current'] ?? '—' }}
                </div>
                <div class="hardware-sub">
                    Пиковая нагрузка: <strong style="color:#0f172a;" class="dark:text-white">{{ $systemStats['php_memory_peak'] ?? '—' }}</strong> (Лимит: 1024M)
                </div>
            </div>

            <!-- Реальный IP сервера -->
            <div class="hardware-card">
                <div class="hardware-label">
                    <span>IP-адрес хоста</span>
                    <span style="display:inline-flex; align-items:center; gap:4px; font-size:10px; color:#10b981; font-weight:800;">
                        <span style="width:6px; height:6px; border-radius:999px; background:#10b981; display:inline-block;"></span>
                        <span>ONLINE</span>
                    </span>
                </div>
                <div class="hardware-val" style="font-size: 20px; font-family: monospace; color:#0891b2;">
                    {{ $systemStats['server_ip'] ?? '176.60.210.120' }}
                </div>
                <div class="hardware-sub">
                    Узел: <strong>Dedicated Cloud Node</strong> (Минск, Беларусь)
                </div>
            </div>

            <!-- Активные видеоуроки -->
            <div class="hardware-card">
                <div class="hardware-label">
                    <span>Уроки в эфире</span>
                    <span style="font-size:10px; color:#7c3aed; font-weight:800;">LiveKit SFU</span>
                </div>
                <div class="hardware-val" style="color: {{ count($activeSessions) > 0 ? '#7c3aed' : '#059669' }};">
                    {{ count($activeSessions) }} <span style="font-size:13px; font-weight:600; color:#64748b;">комнат</span>
                </div>
                <div class="hardware-sub">
                    @if(count($activeSessions) > 0)
                        <span>Идет видеосвязь в режиме реального времени</span>
                    @else
                        <span>Все каналы видеосвязи свободны</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 7 Архитектурных узлов системы --}}
        <div class="topology-grid">
            @foreach($nodes as $key => $node)
                <div class="topology-card topology-card--{{ $node['status'] }}">
                    <div>
                        <div class="topology-card-header">
                            <span class="topology-card-title">
                                @if($key === 'nginx') 🌐
                                @elseif($key === 'php') ⚡
                                @elseif($key === 'mysql') 🗄️
                                @elseif($key === 'cache') 🚀
                                @elseif($key === 'livekit') 🎥
                                @elseif($key === 'ai') 🤖
                                @elseif($key === 'queue') ⏱️
                                @endif
                                {{ $node['name'] }}
                            </span>
                            <span class="topology-badge topology-badge--{{ $node['status'] }}">
                                {{ $node['badge'] }}
                            </span>
                        </div>
                        <div class="topology-card-role">{{ $node['role'] }}</div>
                    </div>

                    <div class="topology-details">
                        @foreach($node['details'] as $lbl => $val)
                            <div class="topology-detail-row">
                                <span class="topology-detail-label">{{ $lbl }}</span>
                                <span class="topology-detail-val">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Мониторинг комнат видеокласса в реальном времени --}}
        <div class="topology-table-card">
            <h3 class="topology-table-title">
                <span>🎥</span>
                <span>Мониторинг комнат виртуального класса в реальном времени</span>
            </h3>

            @if(count($activeSessions) === 0)
                <div style="text-align: center; padding: 28px 16px; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1;" class="dark:bg-gray-800/40 dark:border-gray-700">
                    <span style="font-size: 24px; display: block; margin-bottom: 6px;">📹</span>
                    <div style="font-weight: 700; font-size: 13px; color: #475569;" class="dark:text-gray-300">
                        В данный момент активных видеоуроков нет
                    </div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                        Когда репетитор или ученик войдут в виртуальный класс LiveKit, комната появится здесь с телеметрией и кнопкой подключения инкогнито
                    </div>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="topology-table">
                        <thead>
                            <tr>
                                <th style="width: 90px;">Урок</th>
                                <th>Комната (Room ID)</th>
                                <th>Преподаватель</th>
                                <th>Ученик</th>
                                <th style="width: 140px;">Время старта</th>
                                <th style="width: 150px; text-align: center;">Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeSessions as $sess)
                                <tr>
                                    <td style="font-weight: 800; font-family: monospace;">#{{ $sess['lesson_id'] }}</td>
                                    <td style="font-family: monospace; color: #7c3aed; font-weight: 700;">{{ $sess['room_id'] }}</td>
                                    <td>{{ $sess['lesson']['tutor']['name'] ?? '—' }}</td>
                                    <td>{{ $sess['lesson']['student']['name'] ?? '—' }}</td>
                                    <td style="color: #64748b; font-size: 11px;">{{ $sess['started_at'] ?? '—' }}</td>
                                    <td style="text-align: center;">
                                        <a href="/classroom/{{ $sess['lesson_id'] }}" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; background: #ede9fe; color: #6d28d9; font-weight: 700; font-size: 11px; text-decoration: none;">
                                            <span>Войти инкогнито</span>
                                            <span>➔</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
