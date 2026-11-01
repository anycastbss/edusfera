<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemOperationsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'filament.site-admin.pages.system-operations-page';

    protected static ?string $navigationLabel = 'Инструменты инженера (DevOps)';

    protected static ?string $title = 'Операции обслуживания и кэш';

    protected static ?string $navigationGroup = 'Инструменты инженера';

    protected static ?int $navigationSort = 1;

    public array $failedJobs = [];

    public int $jobsCount = 0;

    public string $consoleOutput = '';

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
        $this->refreshOperationsData();
    }

    public function refreshOperationsData(): void
    {
        try {
            if (Schema::hasTable('jobs')) {
                $this->jobsCount = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $this->failedJobs = DB::table('failed_jobs')
                    ->latest('failed_at')
                    ->limit(20)
                    ->get()
                    ->map(fn ($j) => (array) $j)
                    ->toArray();
            }
        } catch (\Throwable $e) {}
    }

    public function runArtisanCommand(string $command): void
    {
        try {
            Artisan::call($command);
            $output = Artisan::output();
            $this->consoleOutput = ">>> php artisan {$command}\n".trim($output);

            $this->refreshOperationsData();

            Notification::make()
                ->title("Команда {$command} выполнена")
                ->body('Успешно завершено.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->consoleOutput = ">>> php artisan {$command}\nОШИБКА: ".$e->getMessage();
            Notification::make()
                ->title('Сбой выполнения')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function retryJob(string $id): void
    {
        $this->runArtisanCommand("queue:retry {$id}");
    }

    public function retryAllJobs(): void
    {
        $this->runArtisanCommand('queue:retry all');
    }

    public function flushFailedJobs(): void
    {
        $this->runArtisanCommand('queue:flush');
    }
}
