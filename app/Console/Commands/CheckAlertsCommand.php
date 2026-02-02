<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class CheckAlertsCommand extends Command
{
    protected $signature = 'monitor:check-alerts';

    protected $description = 'Check all active alerts against current metrics';

    public function __construct(
        protected AlertService $alertService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking alerts...');

        $triggered = $this->alertService->checkAlerts();

        if ($triggered->isEmpty()) {
            $this->info('No alerts triggered.');
            return self::SUCCESS;
        }

        $this->warn("Triggered {$triggered->count()} alert(s):");

        foreach ($triggered as $item) {
            $this->line(sprintf(
                '  - [%s] %s: %s = %.2f (threshold: %s %.2f)',
                $item['node']->name,
                $item['alert']->metric_type,
                $item['alert']->metric_type,
                $item['value'],
                $item['alert']->getConditionLabel(),
                $item['alert']->threshold
            ));
        }

        return self::SUCCESS;
    }
}
