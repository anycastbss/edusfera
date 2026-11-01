<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformTelemetry;
use Illuminate\Console\Command;

class PruneTelemetryCommand extends Command
{
    protected $signature = 'telemetry:prune {--days=14 : Number of days of telemetry to retain}';

    protected $description = 'Prune platform telemetry logs older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Pruning platform telemetry records older than {$days} days ({$cutoff->toDateTimeString()})...");

        $deleted = PlatformTelemetry::where('created_at', '<', $cutoff)->delete();

        $this->info("Successfully deleted {$deleted} obsolete telemetry records.");

        return self::SUCCESS;
    }
}
