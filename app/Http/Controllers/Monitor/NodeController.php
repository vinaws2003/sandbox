<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreNodeRequest;
use App\Http\Requests\Monitor\UpdateNodeRequest;
use App\Models\Alert;
use App\Models\Node;
use App\Providers\MonitorServiceProvider;
use App\Services\MetricService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NodeController extends Controller
{
    public function __construct(
        protected MetricService $metricService
    ) {}

    public function index(): Response
    {
        $nodes = Node::query()
            ->withCount('alerts')
            ->orderBy('name')
            ->get()
            ->map(function (Node $node) {
                $latestMetrics = $this->metricService->getLatestMetrics($node);

                return [
                    'id' => $node->id,
                    'name' => $node->name,
                    'type' => $node->type,
                    'host' => $node->host,
                    'port' => $node->port,
                    'is_active' => $node->is_active,
                    'alerts_count' => $node->alerts_count,
                    'last_updated' => $latestMetrics->max('recorded_at')?->diffForHumans(),
                ];
            });

        return Inertia::render('Monitor/Nodes/Index', [
            'nodes' => $nodes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Monitor/Nodes/Create', [
            'nodeTypes' => [
                ['value' => 'synology', 'label' => 'Synology NAS'],
                ['value' => 'docker', 'label' => 'Docker Host'],
                ['value' => 'galera', 'label' => 'Galera Cluster Node'],
                ['value' => 'laravel_app', 'label' => 'Laravel Application'],
            ],
        ]);
    }

    public function store(StoreNodeRequest $request): RedirectResponse
    {
        $node = Node::create($request->validated());

        return redirect()
            ->route('monitor.nodes.show', $node)
            ->with('success', "Node '{$node->name}' created successfully.");
    }

    public function show(Request $request, Node $node): Response
    {
        $range = $request->get('range', '1h');

        $latestMetrics = $this->metricService->getLatestMetrics($node);

        // Get chart data for key metrics based on node type
        $chartMetrics = $this->getChartMetricsForType($node->type);
        $charts = [];

        foreach ($chartMetrics as $metricType) {
            $charts[$metricType] = $this->metricService->getChartData($node, $metricType, $range);
        }

        $recentAlerts = $node->alertLogs()
            ->with('alert')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'metric_type' => $log->alert->metric_type,
                'value' => $log->metric_value,
                'message' => $log->message,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('Monitor/NodeDetail', [
            'node' => [
                'id' => $node->id,
                'name' => $node->name,
                'type' => $node->type,
                'host' => $node->host,
                'port' => $node->port,
                'is_active' => $node->is_active,
                'created_at' => $node->created_at->format('Y-m-d H:i:s'),
            ],
            'metrics' => $latestMetrics->keyBy('type')->map(fn ($m) => [
                'value' => $m->value,
                'recorded_at' => $m->recorded_at->diffForHumans(),
                'metadata' => $m->metadata,
            ]),
            'charts' => $charts,
            'recentAlerts' => $recentAlerts,
            'range' => $range,
        ]);
    }

    public function edit(Node $node): Response
    {
        return Inertia::render('Monitor/Nodes/Edit', [
            'node' => [
                'id' => $node->id,
                'name' => $node->name,
                'type' => $node->type,
                'host' => $node->host,
                'port' => $node->port,
                'is_active' => $node->is_active,
                'credentials' => $node->credentials,
            ],
            'nodeTypes' => [
                ['value' => 'synology', 'label' => 'Synology NAS'],
                ['value' => 'docker', 'label' => 'Docker Host'],
                ['value' => 'galera', 'label' => 'Galera Cluster Node'],
                ['value' => 'laravel_app', 'label' => 'Laravel Application'],
            ],
        ]);
    }

    public function update(UpdateNodeRequest $request, Node $node): RedirectResponse
    {
        $node->update($request->validated());

        return redirect()
            ->route('monitor.nodes.show', $node)
            ->with('success', "Node '{$node->name}' updated successfully.");
    }

    public function destroy(Node $node): RedirectResponse
    {
        $name = $node->name;
        $node->delete();

        return redirect()
            ->route('monitor.nodes.index')
            ->with('success', "Node '{$name}' deleted successfully.");
    }

    public function testConnection(Node $node): RedirectResponse
    {
        $collector = MonitorServiceProvider::getCollectorFor($node->type);

        if (!$collector) {
            return back()->with('error', "No collector available for node type: {$node->type}");
        }

        $success = $collector->testConnection($node);

        if ($success) {
            return back()->with('success', 'Connection successful!');
        }

        return back()->with('error', 'Connection failed. Please check the host and credentials.');
    }

    protected function getChartMetricsForType(string $type): array
    {
        return match ($type) {
            'synology' => ['cpu', 'memory', 'disk', 'network_in', 'network_out'],
            'docker' => ['container_cpu', 'container_memory'],
            'galera' => ['wsrep_cluster_size', 'wsrep_flow_control_paused'],
            'laravel_app' => ['response_time', 'queue_size', 'failed_jobs'],
            default => ['cpu', 'memory'],
        };
    }
}
