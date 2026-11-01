<x-filament-panels::page>
    <style>
        .ops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .ops-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .ops-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .ops-btn:hover {
            background: rgba(125, 57, 235, 0.3);
            border-color: #7D39EB;
        }
        .ops-btn--primary {
            background: #7D39EB !important;
            border-color: #7D39EB !important;
            box-shadow: 0 4px 14px rgba(125, 57, 235, 0.35);
        }
        .ops-btn--danger {
            background: rgba(239, 68, 68, 0.15) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
            color: #f87171 !important;
        }
        .ops-btn--danger:hover {
            background: rgba(239, 68, 68, 0.3) !important;
        }
        .terminal-box {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            line-height: 1.6;
            color: #22c55e;
            white-space: pre-wrap;
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>

    {{-- Консольный терминал вывода команд --}}
    @if(!empty($consoleOutput))
        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #94a3b8;">Терминал вывода Artisan</span>
                <button type="button" wire:click="$set('consoleOutput', '')" style="background: transparent; border: none; color: #64748b; font-size: 11px; cursor: pointer;">Очистить</button>
            </div>
            <div class="terminal-box">{{ $consoleOutput }}</div>
        </div>
    @endif

    {{-- Сетка блоков управления --}}
    <div class="ops-grid">
        {{-- Блок 1: Управление кэшем --}}
        <div class="ops-card">
            <h3 style="font-size: 15px; font-weight: 700; color: #fff; margin: 0 0 6px 0;">⚡ Кэш-контроллеры</h3>
            <p style="font-size: 12px; color: #94a3b8; margin: 0 0 16px 0;">Мгновенная очистка и перекомпиляция слоёв кэширования</p>

            <button type="button" wire:click="runArtisanCommand('optimize')" class="ops-btn ops-btn--primary">
                🚀 Полная оптимизация (optimize)
            </button>
            <button type="button" wire:click="runArtisanCommand('config:clear')" class="ops-btn">
                ⚙️ Сбросить кэш конфигов (config:clear)
            </button>
            <button type="button" wire:click="runArtisanCommand('route:clear')" class="ops-btn">
                🛣️ Сбросить кэш роутов (route:clear)
            </button>
            <button type="button" wire:click="runArtisanCommand('view:clear')" class="ops-btn">
                🎨 Сбросить кэш Blade (view:clear)
            </button>
            <button type="button" wire:click="runArtisanCommand('cache:clear')" class="ops-btn">
                🗃️ Сбросить кэш приложения (cache:clear)
            </button>
        </div>

        {{-- Блок 2: Очереди и воркеры --}}
        <div class="ops-card">
            <h3 style="font-size: 15px; font-weight: 700; color: #fff; margin: 0 0 6px 0;">⏱️ Очереди и воркеры</h3>
            <p style="font-size: 12px; color: #94a3b8; margin: 0 0 16px 0;">
                В очереди: <strong>{{ $jobsCount }}</strong> | Упало: <strong style="color: {{ count($failedJobs) > 0 ? '#ef4444' : '#10b981' }}">{{ count($failedJobs) }}</strong>
            </p>

            <button type="button" wire:click="runArtisanCommand('queue:restart')" class="ops-btn">
                🔄 Перезапустить воркеры (queue:restart)
            </button>
            <button type="button" wire:click="retryAllJobs" class="ops-btn" style="color: #38bdf8;">
                🔁 Повторить все упавшие задачи
            </button>
            <button type="button" wire:confirm="Удалить все записи из таблицы failed_jobs?" wire:click="flushFailedJobs" class="ops-btn ops-btn--danger">
                🗑️ Очистить список сбоев (queue:flush)
            </button>
        </div>

        {{-- Блок 3: Тестирование и диагностика --}}
        <div class="ops-card">
            <h3 style="font-size: 15px; font-weight: 700; color: #fff; margin: 0 0 6px 0;">🧪 Инженерные тесты</h3>
            <p style="font-size: 12px; color: #94a3b8; margin: 0 0 16px 0;">Проверка целостности и готовности сервисов</p>

            <button type="button" wire:click="runArtisanCommand('migrate:status')" class="ops-btn">
                📋 Статус миграций базы данных
            </button>
            <button type="button" wire:click="runArtisanCommand('schedule:list')" class="ops-btn">
                ⏰ Список расписания задач (Cron)
            </button>
            <button type="button" wire:click="runArtisanCommand('about')" class="ops-btn">
                ℹ️ Справка о среде (php artisan about)
            </button>
        </div>
    </div>

    {{-- Таблица Failed Jobs --}}
    <div class="ops-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Журнал упавших фоновых задач (Failed Jobs)</h3>
                <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">Задачи, завершившиеся критической ошибкой во время выполнения</p>
            </div>
            <button type="button" wire:click="refreshOperationsData" class="ops-btn" style="width: auto; margin: 0;">
                🔄 Обновить
            </button>
        </div>

        @if(count($failedJobs) === 0)
            <div style="text-align: center; padding: 28px; color: #10b981; font-weight: 600; font-size: 13px;">
                ✓ Очередь полностью исправна. Упавших задач нет!
            </div>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #94a3b8;">
                            <th style="padding: 10px;">ID</th>
                            <th style="padding: 10px;">Очередь</th>
                            <th style="padding: 10px;">Дата сбоя</th>
                            <th style="padding: 10px;">Ошибка</th>
                            <th style="padding: 10px;">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $job)
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #fff;">
                                <td style="padding: 10px; font-weight: 700;">#{{ $job['id'] }}</td>
                                <td style="padding: 10px; font-family: monospace; color: #38bdf8;">{{ $job['queue'] ?? 'default' }}</td>
                                <td style="padding: 10px; color: #94a3b8;">{{ $job['failed_at'] }}</td>
                                <td style="padding: 10px; font-family: monospace; font-size: 11px; color: #f87171; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ Str::limit($job['exception'] ?? '—', 120) }}
                                </td>
                                <td style="padding: 10px;">
                                    <button type="button" wire:click="retryJob('{{ $job['id'] }}')" style="background: rgba(125, 57, 235, 0.2); border: 1px solid #7D39EB; color: #fff; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 11px;">
                                        Повторить
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
