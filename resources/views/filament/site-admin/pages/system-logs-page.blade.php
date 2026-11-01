<x-filament-panels::page>
    <style>
        .log-tabs-nav {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 12px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .log-tab-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .log-tab-btn:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.25);
        }
        .log-tab-btn--active {
            background: #7D39EB !important;
            color: #fff !important;
            border-color: #7D39EB !important;
            box-shadow: 0 4px 14px rgba(125, 57, 235, 0.35);
        }
        .log-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .log-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .log-badge--success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .log-badge--failed, .log-badge--error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .log-badge--warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .log-badge--info {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .code-box {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            line-height: 1.5;
            color: #e2e8f0;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        .code-line {
            padding: 2px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .code-line--error {
            color: #f87171;
            font-weight: 600;
        }
        .code-line--warning {
            color: #fbbf24;
        }
    </style>

    {{-- Навигация по табам логов --}}
    <div class="log-tabs-nav">
        <button type="button" wire:click="setTab('audit')" class="log-tab-btn {{ $activeTab === 'audit' ? 'log-tab-btn--active' : '' }}">
            👤 Аудит пользователей (Входы и действия)
        </button>
        <button type="button" wire:click="setTab('webhooks')" class="log-tab-btn {{ $activeTab === 'webhooks' ? 'log-tab-btn--active' : '' }}">
            💳 Логи платежных вебхуков (Alfa-Bank)
        </button>
        <button type="button" wire:click="setTab('ai')" class="log-tab-btn {{ $activeTab === 'ai' ? 'log-tab-btn--active' : '' }}">
            🤖 Логи запросов к ИИ (Gemini)
        </button>
        <button type="button" wire:click="setTab('system')" class="log-tab-btn {{ $activeTab === 'system' ? 'log-tab-btn--active' : '' }}">
            📑 Системные ошибки (laravel.log)
        </button>
    </div>

    {{-- ТАБ 1: АУДИТ ПОЛЬЗОВАТЕЛЕЙ --}}
    @if($activeTab === 'audit')
        <div class="log-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Журнал сессий и действий пользователей</h3>
                <div style="display: flex; gap: 8px;">
                    <select wire:model.live="auditFilterCategory" style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 12px; padding: 6px 12px;">
                        <option value="all">Все категории</option>
                        <option value="auth">Авторизация (Вход/Выход)</option>
                        <option value="profile">Профиль и анкеты</option>
                        <option value="lesson">Занятия</option>
                        <option value="payment">Платежи и подписки</option>
                        <option value="security">Безопасность</option>
                    </select>
                    <button type="button" wire:click="loadData" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 12px; padding: 6px 12px; cursor: pointer;">
                        🔄 Обновить
                    </button>
                </div>
            </div>

            @if(count($auditLogs) === 0)
                <div style="text-align: center; padding: 32px; color: #94a3b8; font-size: 13px;">
                    Записей аудита в выбранной категории пока нет. Логирование ведется в реальном времени.
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #94a3b8;">
                                <th style="padding: 10px;">Дата / Время</th>
                                <th style="padding: 10px;">Пользователь</th>
                                <th style="padding: 10px;">Роль</th>
                                <th style="padding: 10px;">Событие</th>
                                <th style="padding: 10px;">IP / Локация</th>
                                <th style="padding: 10px;">Статус</th>
                                <th style="padding: 10px;">Детали</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditLogs as $log)
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff;">
                                    <td style="padding: 10px; color: #94a3b8; font-family: monospace;">
                                        {{ \Carbon\Carbon::parse($log['created_at'])->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td style="padding: 10px;">
                                        <strong>{{ $log['user']['name'] ?? ($log['user_email'] ?? 'Гость') }}</strong>
                                        <div style="font-size: 11px; color: #64748b;">{{ $log['user_email'] ?? '—' }}</div>
                                    </td>
                                    <td style="padding: 10px;">
                                        <span class="log-badge log-badge--info">{{ $log['user_role'] ?? 'guest' }}</span>
                                    </td>
                                    <td style="padding: 10px; font-weight: 600;">
                                        @if($log['action'] === 'login_success') 🟢 Успешный вход
                                        @elseif($log['action'] === 'login_failed') 🔴 Неверный пароль
                                        @elseif($log['action'] === 'logout') ⚪ Выход
                                        @else {{ $log['action'] }}
                                        @endif
                                    </td>
                                    <td style="padding: 10px; font-family: monospace; color: #38bdf8;">
                                        {{ $log['ip_address'] ?? '—' }}
                                    </td>
                                    <td style="padding: 10px;">
                                        <span class="log-badge log-badge--{{ $log['status'] }}">{{ $log['status'] }}</span>
                                    </td>
                                    <td style="padding: 10px; font-size: 11px; color: #94a3b8; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ json_encode($log['details'] ?? [], JSON_UNESCAPED_UNICODE) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ТАБ 2: ВЕБХУКИ ПЛАТЕЖЕЙ --}}
    @if($activeTab === 'webhooks')
        <div class="log-card">
            <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 16px 0;">Входящие вебхуки эквайринга (Alfa-Bank / WebPay)</h3>
            @if(count($webhookLogs) === 0)
                <div style="text-align: center; padding: 32px; color: #94a3b8; font-size: 13px;">
                    Вебхуков пока не поступало или таблица пуста.
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #94a3b8;">
                                <th style="padding: 10px;">Дата</th>
                                <th style="padding: 10px;">Провайдер</th>
                                <th style="padding: 10px;">Заказ / Транзакция</th>
                                <th style="padding: 10px;">HTTP Статус</th>
                                <th style="padding: 10px;">IP отправителя</th>
                                <th style="padding: 10px;">Payload (JSON)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($webhookLogs as $wh)
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff;">
                                    <td style="padding: 10px; color: #94a3b8; font-family: monospace;">
                                        {{ \Carbon\Carbon::parse($wh['created_at'])->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td style="padding: 10px; font-weight: 700; color: #f59e0b;">{{ $wh['provider'] ?? 'AlfaBank' }}</td>
                                    <td style="padding: 10px; font-family: monospace;">{{ $wh['order_id'] ?? ($wh['transaction_id'] ?? '—') }}</td>
                                    <td style="padding: 10px;">
                                        <span class="log-badge {{ ($wh['response_status'] ?? 200) < 400 ? 'log-badge--success' : 'log-badge--failed' }}">
                                            {{ $wh['response_status'] ?? '200' }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px; font-family: monospace;">{{ $wh['ip_address'] ?? '—' }}</td>
                                    <td style="padding: 10px; font-family: monospace; font-size: 11px; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ is_array($wh['payload'] ?? null) ? json_encode($wh['payload'], JSON_UNESCAPED_UNICODE) : ($wh['payload'] ?? '—') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ТАБ 3: ЗАПРОСЫ К ИИ --}}
    @if($activeTab === 'ai')
        <div class="log-card">
            <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 16px 0;">Журнал вызовов нейросетей (Google Gemini)</h3>
            @if(count($aiLogs) === 0)
                <div style="text-align: center; padding: 32px; color: #94a3b8; font-size: 13px;">
                    Журнал вызовов ИИ чист.
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #94a3b8;">
                                <th style="padding: 10px;">Дата</th>
                                <th style="padding: 10px;">Преподаватель</th>
                                <th style="padding: 10px;">Модель</th>
                                <th style="padding: 10px;">Токены</th>
                                <th style="padding: 10px;">Задержка</th>
                                <th style="padding: 10px;">Промпт / Задача</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aiLogs as $ai)
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff;">
                                    <td style="padding: 10px; color: #94a3b8; font-family: monospace;">
                                        {{ \Carbon\Carbon::parse($ai['created_at'])->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td style="padding: 10px;">{{ $ai['user']['name'] ?? '—' }}</td>
                                    <td style="padding: 10px; font-weight: 700; color: #38bdf8;">{{ $ai['model'] ?? 'gemini-2.5-flash' }}</td>
                                    <td style="padding: 10px; font-family: monospace;">{{ $ai['tokens_used'] ?? '—' }}</td>
                                    <td style="padding: 10px;">{{ $ai['latency_ms'] ?? '—' }} ms</td>
                                    <td style="padding: 10px; font-size: 11px; max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $ai['prompt_preview'] ?? ($ai['prompt'] ?? '—') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ТАБ 4: СИСТЕМНЫЙ LARAVEL.LOG --}}
    @if($activeTab === 'system')
        <div class="log-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Просмотрщик storage/logs/laravel.log</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">Чтение последних событий ядра и стеков ошибок</p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" wire:model.live.debounce.300ms="logSearchQuery" placeholder="Поиск по тексту..." style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 12px; padding: 6px 12px; width: 180px;">
                    
                    <select wire:model.live="systemLogLevel" style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 12px; padding: 6px 12px;">
                        <option value="all">Все уровни</option>
                        <option value="error">Только ERROR</option>
                        <option value="warning">Только WARNING</option>
                        <option value="info">Только INFO</option>
                    </select>

                    <button type="button" wire:click="loadSystemLogs" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 12px; padding: 6px 12px; cursor: pointer;">
                        🔄 Обновить
                    </button>

                    <button type="button" wire:confirm="Очистить весь файл логов laravel.log?" wire:click="clearSystemLog" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; color: #f87171; font-size: 12px; padding: 6px 12px; cursor: pointer;">
                        🗑️ Очистить лог
                    </button>
                </div>
            </div>

            <div class="code-box">
                @forelse($systemLogLines as $line)
                    <div class="code-line {{ str_contains($line, '.ERROR:') ? 'code-line--error' : (str_contains($line, '.WARNING:') ? 'code-line--warning' : '') }}">
                        {{ $line }}
                    </div>
                @empty
                    <div style="color: #64748b; padding: 16px; text-align: center;">Файл логов пуст или по заданному фильтру ничего не найдено.</div>
                @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
