<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SplFileInfo;
use Throwable;

class CleanupExamTempDirectories extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exam:cleanup-temp
                            {--minutes=30 : Удалять директории старше указанного количества минут}
                            {--dry-run : Показать список директорий без удаления}';

    /**
     * The console command description.
     */
    protected $description = 'Удаляет осиротевшие временные директории storage/app/temp_exam_*';

    public function handle(): int
    {
        $minutes = max(5, (int) $this->option('minutes'));
        $dryRun = (bool) $this->option('dry-run');
        $thresholdTimestamp = now()->subMinutes($minutes)->getTimestamp();
        $basePath = storage_path('app');

        if (! File::isDirectory($basePath)) {
            $this->warn("Каталог {$basePath} не существует.");
            return self::SUCCESS;
        }

        $directories = File::directories($basePath);
        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($directories as $dirPath) {
            $dirName = basename($dirPath);

            // Match only exam generator temp directories
            if (! str_starts_with($dirName, 'temp_exam_')) {
                continue;
            }

            $lastModified = $this->resolveLastModified($dirPath);

            if ($lastModified >= $thresholdTimestamp) {
                continue;
            }

            $dirSize = $this->calculateSize($dirPath);

            if ($dryRun) {
                $this->line(sprintf(
                    '[DRY-RUN] %s — возраст: %d мин, размер: %s',
                    $dirName,
                    (int) floor((time() - $lastModified) / 60),
                    $this->formatBytes($dirSize)
                ));
                $deletedCount++;
                $freedBytes += $dirSize;
                continue;
            }

            try {
                if (File::deleteDirectory($dirPath)) {
                    $deletedCount++;
                    $freedBytes += $dirSize;
                }
            } catch (Throwable $e) {
                Log::warning('exam:cleanup-temp: failed to remove dir', [
                    'path' => $dirPath,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Ошибка удаления {$dirName}: {$e->getMessage()}");
            }
        }

        $formattedSize = $this->formatBytes($freedBytes);

        if ($deletedCount > 0) {
            Log::info('exam:cleanup-temp: cleanup completed', [
                'deleted_count' => $deletedCount,
                'freed_bytes' => $freedBytes,
                'dry_run' => $dryRun,
            ]);
        }

        $this->info(
            $dryRun
                ? "Найдено директорий к удалению: {$deletedCount} (освободится {$formattedSize})."
                : "Удалено директорий: {$deletedCount} (освобождено {$formattedSize})."
        );

        return self::SUCCESS;
    }

    private function resolveLastModified(string $dirPath): int
    {
        $latest = (int) @filemtime($dirPath);

        /** @var SplFileInfo $file */
        foreach (File::allFiles($dirPath) as $file) {
            $mtime = $file->getMTime();
            if ($mtime > $latest) {
                $latest = $mtime;
            }
        }

        return $latest;
    }

    private function calculateSize(string $dirPath): int
    {
        $total = 0;

        /** @var SplFileInfo $file */
        foreach (File::allFiles($dirPath) as $file) {
            $total += $file->getSize();
        }

        return $total;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }
}

