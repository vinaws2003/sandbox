<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\Metric;
use App\Models\Node;
use App\Notifications\AlertTriggeredNotification;
use App\Notifications\SlackAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AlertService
{
    public function __construct(
        protected MetricService $metricService
    ) {}

    public function checkAlerts(): Collection
    {
        $alerts = Alert::query()
            ->active()
            ->with('node')
            ->get();

        $triggeredAlerts = collect();

        foreach ($alerts as $alert) {
            $triggered = $this->evaluateAlert($alert);

            if ($triggered->isNotEmpty()) {
                $triggeredAlerts = $triggeredAlerts->merge($triggered);
            }
        }

        return $triggeredAlerts;
    }

    public function evaluateAlert(Alert $alert): Collection
    {
        $triggeredFor = collect();

        // If alert is on cooldown, skip
        if ($alert->isOnCooldown()) {
            return $triggeredFor;
        }

        // Get nodes to check
        if ($alert->node_id) {
            // Alert is for a specific node
            $nodes = collect([$alert->node]);
        } else {
            // Global alert - check all active nodes
            $nodes = Node::active()->get();
        }

        foreach ($nodes as $node) {
            $value = $this->metricService->getLatestMetricValue($node, $alert->metric_type);

            if ($value === null) {
                continue;
            }

            if ($alert->evaluate($value)) {
                $this->triggerAlert($alert, $node, $value);
                $triggeredFor->push([
                    'alert' => $alert,
                    'node' => $node,
                    'value' => $value,
                ]);
            }
        }

        return $triggeredFor;
    }

    public function triggerAlert(Alert $alert, Node $node, float $value): void
    {
        // Log the alert
        $message = $this->buildAlertMessage($alert, $node, $value);

        AlertLog::create([
            'alert_id' => $alert->id,
            'node_id' => $node->id,
            'metric_value' => $value,
            'message' => $message,
        ]);

        // Update last triggered timestamp
        $alert->update(['last_triggered_at' => now()]);

        // Send notification
        $this->sendNotification($alert, $node, $value, $message);

        Log::info('Alert triggered', [
            'alert_id' => $alert->id,
            'node' => $node->name,
            'metric_type' => $alert->metric_type,
            'value' => $value,
            'threshold' => $alert->threshold,
        ]);
    }

    protected function buildAlertMessage(Alert $alert, Node $node, float $value): string
    {
        $condition = $alert->getConditionLabel();
        $threshold = number_format($alert->threshold, 2);
        $actualValue = number_format($value, 2);

        return sprintf(
            '[%s] %s: %s is %s (threshold: %s %s)',
            $node->name,
            $alert->metric_type,
            $actualValue,
            $condition,
            $condition,
            $threshold
        );
    }

    protected function sendNotification(Alert $alert, Node $node, float $value, string $message): void
    {
        try {
            match ($alert->notification_channel) {
                'mail' => $this->sendEmailNotification($alert, $node, $value, $message),
                'slack' => $this->sendSlackNotification($alert, $node, $value, $message),
                'database' => $this->logDatabaseNotification($alert, $node, $value, $message),
            };
        } catch (\Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'channel' => $alert->notification_channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendEmailNotification(Alert $alert, Node $node, float $value, string $message): void
    {
        if (!$alert->notification_target) {
            return;
        }

        Notification::route('mail', $alert->notification_target)
            ->notify(new AlertTriggeredNotification($alert, $node, $value, $message));
    }

    protected function sendSlackNotification(Alert $alert, Node $node, float $value, string $message): void
    {
        if (!$alert->notification_target) {
            return;
        }

        Notification::route('slack', $alert->notification_target)
            ->notify(new SlackAlertNotification($alert, $node, $value, $message));
    }

    protected function logDatabaseNotification(Alert $alert, Node $node, float $value, string $message): void
    {
        // Already logged via AlertLog, this is just for the database channel
        Log::channel('single')->info('Monitor Alert', [
            'node' => $node->name,
            'metric' => $alert->metric_type,
            'value' => $value,
            'message' => $message,
        ]);
    }

    public function getRecentAlertLogs(int $limit = 50): Collection
    {
        return AlertLog::query()
            ->with(['alert', 'node'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getAlertLogsForNode(Node $node, int $limit = 50): Collection
    {
        return AlertLog::query()
            ->where('node_id', $node->id)
            ->with('alert')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getActiveAlertsCount(): int
    {
        return Alert::active()->count();
    }

    public function getRecentTriggeredCount(int $hours = 24): int
    {
        return AlertLog::query()
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }
}
