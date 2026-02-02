<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\MetricService;
use Inertia\Inertia;
use Inertia\Response;

class GaleraClusterController extends Controller
{
    public function __construct(
        protected MetricService $metricService
    ) {}

    public function index(): Response
    {
        $galeraNodes = Node::query()
            ->where('type', 'galera')
            ->orderBy('name')
            ->get()
            ->map(function (Node $node) {
                $metrics = $this->metricService->getLatestMetrics($node);

                $clusterSize = $metrics->firstWhere('type', 'wsrep_cluster_size')?->value ?? 0;
                $localState = (int) ($metrics->firstWhere('type', 'wsrep_local_state')?->value ?? 0);
                $ready = $metrics->firstWhere('type', 'wsrep_ready')?->value ?? 0;
                $connected = $metrics->firstWhere('type', 'wsrep_connected')?->value ?? 0;
                $flowControlPaused = $metrics->firstWhere('type', 'wsrep_flow_control_paused')?->value ?? 0;

                $stateComment = $metrics->firstWhere('type', 'wsrep_local_state')?->metadata['comment'] ?? $this->getStateComment($localState);

                return [
                    'id' => $node->id,
                    'name' => $node->name,
                    'host' => $node->host,
                    'is_active' => $node->is_active,
                    'cluster_size' => (int) $clusterSize,
                    'local_state' => (int) $localState,
                    'state_comment' => $stateComment,
                    'ready' => $ready == 1,
                    'connected' => $connected == 1,
                    'flow_control_paused' => round($flowControlPaused, 4),
                    'status' => $this->determineNodeStatus($localState, $ready, $connected),
                    'last_updated' => $metrics->max('recorded_at')?->diffForHumans() ?? 'Never',
                ];
            });

        // Calculate cluster health
        $totalNodes = $galeraNodes->count();
        $healthyNodes = $galeraNodes->where('status', 'healthy')->count();
        $expectedSize = $galeraNodes->max('cluster_size') ?: $totalNodes;

        $clusterStatus = match (true) {
            $healthyNodes === 0 => 'critical',
            $healthyNodes < $expectedSize => 'warning',
            default => 'healthy',
        };

        return Inertia::render('Monitor/GaleraCluster', [
            'nodes' => $galeraNodes,
            'cluster' => [
                'total_nodes' => $totalNodes,
                'healthy_nodes' => $healthyNodes,
                'expected_size' => $expectedSize,
                'status' => $clusterStatus,
            ],
        ]);
    }

    protected function getStateComment(int $state): string
    {
        return match ($state) {
            1 => 'Joining',
            2 => 'Donor/Desynced',
            3 => 'Joined',
            4 => 'Synced',
            default => 'Unknown',
        };
    }

    protected function determineNodeStatus(int $localState, $ready, $connected): string
    {
        if ($ready != 1 || $connected != 1) {
            return 'critical';
        }

        return match ($localState) {
            4 => 'healthy',
            3 => 'warning',
            2 => 'warning',
            1 => 'warning',
            default => 'critical',
        };
    }
}
