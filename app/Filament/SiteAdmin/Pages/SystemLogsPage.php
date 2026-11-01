<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Pages;

use App\Models\AiPromptLog;
use App\Models\PaymentWebhookLog;
use App\Models\UserAuditLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class SystemLogsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.site-admin.pages.system-logs-page';

    protected static ?string $navigationLabel = 'Логи и Аудит (Deep Logs)';

    protected static ?string $title = 'Журналы событий, пользователей и ошибок';

    protected static ?string $navigationGroup = 'Логи и безопасность';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'audit';

    public string $auditFilterCategory = 'all';

    public string $logSearchQuery = '';

    public string $systemLogLevel = 'all';

    public array $auditLogs = [];

    public array $webhookLogs = [];

    public array $aiLogs = [];

    public array $systemLogLines = [];

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
        $this->loadData();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadData();
    }

    public function loadData(): void
    {
        if ($this->activeTab === 'audit') {
            $query = UserAuditLog::query()->with('user')->latest('created_at')->limit(50);
            if ($this->auditFilterCategory !== 'all') {
                $query->where('category', $this->auditFilterCategory);
            }
            $this->auditLogs = $query->get()->toArray();
        } elseif ($this->activeTab === 'webhooks') {
            $this->webhookLogs = PaymentWebhookLog::query()
                ->latest('created_at')
                ->limit(50)
                ->get()
                ->toArray();
        } elseif ($this->activeTab === 'ai') {
            $this->aiLogs = AiPromptLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(50)
                ->get()
                ->toArray();
        } elseif ($this->activeTab === 'system') {
            $this->loadSystemLogs();
        }
    }

    public function loadSystemLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $this->systemLogLines = [];

        if (! File::exists($logPath)) {
            return;
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $lines = array_reverse(array_filter($lines));

        $filtered = [];
        $count = 0;

        foreach ($lines as $line) {
            if ($count >= 120) break;

            if ($this->systemLogLevel !== 'all') {
                if (! str_contains($line, '.' . strtoupper($this->systemLogLevel) . ':')) {
                    continue;
                }
            }

            if (! empty($this->logSearchQuery)) {
                if (! str_ireplace($this->logSearchQuery, '', $line) !== $line && ! stripos($line, $this->logSearchQuery)) {
                    continue;
                }
            }

            $filtered[] = $line;
            $count++;
        }

        $this->systemLogLines = $filtered;
    }

    public function clearSystemLog(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        $this->loadSystemLogs();

        Notification::make()
            ->title('Системный лог очищен')
            ->body('Файл storage/logs/laravel.log успешно очищен.')
            ->success()
            ->send();
    }
}
