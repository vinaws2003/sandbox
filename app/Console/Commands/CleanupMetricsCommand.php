<?php

namespace App\Console\Commands;

use App\Services\MetricService;
use Illuminate\Console\Command;

class CleanupMetricsCommand extends Command
{
    protected $signature = 'monitor:cleanup
                            {--days= : Override retention days from config}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old metrics data based on retention policy';

    public function __construct(
        protected MetricService $metricService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $retentionDays = $this->option('days')
            ?? config('monitor.retention_days', 7);

        $cutoffDate = now()->subDays($retentionDays);

        $this->info("Cleaning up metrics older than {$retentionDays} days (before {$cutoffDate->toDateTimeString()})...");

        if ($this->option('dry-run')) {
            $count = \App\Models\Metric::query()
                ->where('recorded_at', '<', $cutoffDate)
                ->count();

            $this->info("Would delete {$count} metric records.");
            return self::SUCCESS;
        }

        $deleted = $this->metricService->cleanup($retentionDays);

        $this->info("Deleted {$deleted} old metric records.");

        return self::SUCCESS;
    }
}
