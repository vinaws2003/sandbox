<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\MetricService;
use Illuminate\Http\Response;

class PrometheusController extends Controller
{
    public function __construct(
        protected MetricService $metricService
    ) {}

    public function index(): Response
    {
        $output = [];

        // Add help and type comments
        $metricDefinitions = [
            'nas_cpu_usage' => ['help' => 'CPU usage percentage', 'type' => 'gauge'],
            'nas_memory_usage' => ['help' => 'Memory usage percentage', 'type' => 'gauge'],
            'nas_disk_usage' => ['help' => 'Disk usage percentage', 'type' => 'gauge'],
            'nas_temperature' => ['help' => 'Temperature in Celsius', 'type' => 'gauge'],
            'nas_network_in' => ['help' => 'Network incoming traffic KB/s', 'type' => 'gauge'],
            'nas_network_out' => ['help' => 'Network outgoing traffic KB/s', 'type' => 'gauge'],
            'galera_cluster_size' => ['help' => 'Number of nodes in Galera cluster', 'type' => 'gauge'],
            'galera_ready' => ['help' => 'Galera node ready status (1=ready)', 'type' => 'gauge'],
            'galera_connected' => ['help' => 'Galera node connected status (1=connected)', 'type' => 'gauge'],
            'galera_local_state' => ['help' => 'Galera local state (4=Synced)', 'type' => 'gauge'],
            'galera_flow_control_paused' => ['help' => 'Galera flow control paused ratio', 'type' => 'gauge'],
            'laravel_response_time' => ['help' => 'Laravel app response time in seconds', 'type' => 'gauge'],
            'laravel_status' => ['help' => 'Laravel app health status (1=healthy, 0.5=degraded, 0=unhealthy)', 'type' => 'gauge'],
            'laravel_queue_size' => ['help' => 'Laravel queue size', 'type' => 'gauge'],
            'laravel_failed_jobs' => ['help' => 'Laravel failed jobs count', 'type' => 'gauge'],
            'docker_container_status' => ['help' => 'Docker container status (1=running)', 'type' => 'gauge'],
            'docker_container_cpu' => ['help' => 'Docker container CPU usage percentage', 'type' => 'gauge'],
            'docker_container_memory' => ['help' => 'Docker container memory usage percentage', 'type' => 'gauge'],
        ];

        foreach ($metricDefinitions as $name => $def) {
            $output[] = "# HELP {$name} {$def['help']}";
            $output[] = "# TYPE {$name} {$def['type']}";
        }

        $output[] = '';

        // Collect metrics from all nodes
        $nodes = Node::active()->get();

        foreach ($nodes as $node) {
            $metrics = $this->metricService->getLatestMetrics($node);

            foreach ($metrics as $metric) {
                $promName = $this->mapMetricName($node->type, $metric->type);
                if (!$promName) {
                    continue;
                }

                $labels = $this->buildLabels($node, $metric);
                $output[] = "{$promName}{{$labels}} {$metric->value}";
            }
        }

        return response(implode("\n", $output), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    protected function mapMetricName(string $nodeType, string $metricType): ?string
    {
        $mapping = [
            'synology' => [
                'cpu' => 'nas_cpu_usage',
                'memory' => 'nas_memory_usage',
                'disk' => 'nas_disk_usage',
                'temperature' => 'nas_temperature',
                'network_in' => 'nas_network_in',
                'network_out' => 'nas_network_out',
            ],
            'galera' => [
                'wsrep_cluster_size' => 'galera_cluster_size',
                'wsrep_ready' => 'galera_ready',
                'wsrep_connected' => 'galera_connected',
                'wsrep_local_state' => 'galera_local_state',
                'wsrep_flow_control_paused' => 'galera_flow_control_paused',
            ],
            'laravel_app' => [
                'response_time' => 'laravel_response_time',
                'status' => 'laravel_status',
                'queue_size' => 'laravel_queue_size',
                'failed_jobs' => 'laravel_failed_jobs',
            ],
            'docker' => [
                'container_status' => 'docker_container_status',
                'container_cpu' => 'docker_container_cpu',
                'container_memory' => 'docker_container_memory',
            ],
        ];

        return $mapping[$nodeType][$metricType] ?? null;
    }

    protected function buildLabels(Node $node, $metric): string
    {
        $labels = [
            'node' => $this->escapeLabel($node->name),
            'type' => $node->type,
            'host' => $this->escapeLabel($node->host),
        ];

        // Add container name for Docker metrics
        if ($node->type === 'docker' && isset($metric->metadata['container_name'])) {
            $labels['container'] = $this->escapeLabel($metric->metadata['container_name']);
        }

        // Add volume for disk metrics
        if ($metric->type === 'disk' && isset($metric->metadata['volume'])) {
            $labels['volume'] = $this->escapeLabel($metric->metadata['volume']);
        }

        $parts = [];
        foreach ($labels as $key => $value) {
            $parts[] = "{$key}=\"{$value}\"";
        }

        return implode(',', $parts);
    }

    protected function escapeLabel(string $value): string
    {
        return str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
    }
}
