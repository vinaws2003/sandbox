<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\Node;
use App\Services\AlertService;
use App\Services\MetricService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected MetricService $metricService,
        protected AlertService $alertService
    ) {}

    public function index(Request $request): Response
    {
        $nodes = Node::query()
            ->withCount('alerts')
            ->get()
            ->map(function (Node $node) {
                $latestMetrics = $this->metricService->getLatestMetrics($node);

                return [
                    'id' => $node->id,
                    'name' => $node->name,
                    'type' => $node->type,
                    'host' => $node->host,
                    'is_active' => $node->is_active,
                    'alerts_count' => $node->alerts_count,
                    'metrics' => $latestMetrics->keyBy('type')->map(fn ($m) => [
                        'value' => $m->value,
                        'recorded_at' => $m->recorded_at->toIso8601String(),
                        'metadata' => $m->metadata,
                    ]),
                    'last_updated' => $latestMetrics->max('recorded_at')?->toIso8601String(),
                    'status' => $this->determineNodeStatus($node, $latestMetrics),
                ];
            });

        $recentAlerts = AlertLog::query()
            ->with(['alert', 'node'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'node_name' => $log->node->name,
                'metric_type' => $log->alert->metric_type,
                'value' => $log->metric_value,
                'message' => $log->message,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        $stats = [
            'total_nodes' => Node::count(),
            'active_nodes' => Node::active()->count(),
            'active_alerts' => Alert::active()->count(),
            'triggered_24h' => $this->alertService->getRecentTriggeredCount(24),
        ];

        return Inertia::render('Monitor/Dashboard', [
            'nodes' => $nodes,
            'recentAlerts' => $recentAlerts,
            'stats' => $stats,
        ]);
    }

    protected function determineNodeStatus(Node $node, $metrics): string
    {
        if (!$node->is_active) {
            return 'inactive';
        }

        if ($metrics->isEmpty()) {
            return 'unknown';
        }

        // Check for any critical metrics
        $status = $metrics->get('status');
        if ($status && $status->value < 1) {
            return $status->value == 0 ? 'critical' : 'warning';
        }

        // Check CPU/Memory thresholds
        $cpu = $metrics->get('cpu');
        $memory = $metrics->get('memory');

        if (($cpu && $cpu->value > 90) || ($memory && $memory->value > 95)) {
            return 'critical';
        }

        if (($cpu && $cpu->value > 75) || ($memory && $memory->value > 85)) {
            return 'warning';
        }

        return 'healthy';
    }
}
