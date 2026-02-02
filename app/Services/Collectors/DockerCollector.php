<?php

namespace App\Services\Collectors;

use App\Contracts\CollectorInterface;
use App\Exceptions\ConnectionException;
use App\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DockerCollector implements CollectorInterface
{
    public static function supports(): array
    {
        return ['docker'];
    }

    public function testConnection(Node $node): bool
    {
        try {
            $baseUrl = $this->getBaseUrl($node);

            $response = Http::timeout(config('monitor.docker.timeout'))
                ->get("{$baseUrl}/version");

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Docker connection test failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function collect(Node $node): Collection
    {
        $metrics = collect();
        $baseUrl = $this->getBaseUrl($node);

        try {
            $containersResponse = Http::timeout(config('monitor.docker.timeout'))
                ->get("{$baseUrl}/containers/json", ['all' => true]);

            if (!$containersResponse->successful()) {
                throw ConnectionException::apiError($node->host, 'docker', 'Failed to list containers');
            }

            $containers = $containersResponse->json();

            foreach ($containers as $container) {
                $containerId = $container['Id'];
                $containerName = ltrim($container['Names'][0] ?? 'unknown', '/');
                $state = $container['State'] ?? 'unknown';

                // Container status (1 = running, 0 = not running)
                $metrics->push([
                    'type' => 'container_status',
                    'value' => $state === 'running' ? 1 : 0,
                    'metadata' => [
                        'container_id' => substr($containerId, 0, 12),
                        'container_name' => $containerName,
                        'state' => $state,
                    ],
                ]);

                // Get stats for running containers
                if ($state === 'running') {
                    $stats = $this->getContainerStats($baseUrl, $containerId);

                    if ($stats) {
                        // CPU usage
                        $cpuPercent = $this->calculateCpuPercent($stats);
                        if ($cpuPercent !== null) {
                            $metrics->push([
                                'type' => 'container_cpu',
                                'value' => $cpuPercent,
                                'metadata' => [
                                    'container_id' => substr($containerId, 0, 12),
                                    'container_name' => $containerName,
                                ],
                            ]);
                        }

                        // Memory usage
                        $memoryPercent = $this->calculateMemoryPercent($stats);
                        if ($memoryPercent !== null) {
                            $metrics->push([
                                'type' => 'container_memory',
                                'value' => $memoryPercent,
                                'metadata' => [
                                    'container_id' => substr($containerId, 0, 12),
                                    'container_name' => $containerName,
                                ],
                            ]);
                        }
                    }
                }

                // Restart count from inspect
                $inspectResponse = Http::timeout(config('monitor.docker.timeout'))
                    ->get("{$baseUrl}/containers/{$containerId}/json");

                if ($inspectResponse->successful()) {
                    $inspect = $inspectResponse->json();
                    $restartCount = $inspect['RestartCount'] ?? 0;

                    $metrics->push([
                        'type' => 'container_restart_count',
                        'value' => $restartCount,
                        'metadata' => [
                            'container_id' => substr($containerId, 0, 12),
                            'container_name' => $containerName,
                        ],
                    ]);
                }
            }
        } catch (ConnectionException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Docker collection failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            throw ConnectionException::apiError($node->host, 'docker', $e->getMessage());
        }

        return $metrics;
    }

    protected function getContainerStats(string $baseUrl, string $containerId): ?array
    {
        try {
            $response = Http::timeout(config('monitor.docker.timeout'))
                ->get("{$baseUrl}/containers/{$containerId}/stats", [
                    'stream' => false,
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::debug('Failed to get container stats', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function calculateCpuPercent(array $stats): ?float
    {
        $cpuDelta = ($stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0)
            - ($stats['precpu_stats']['cpu_usage']['total_usage'] ?? 0);

        $systemDelta = ($stats['cpu_stats']['system_cpu_usage'] ?? 0)
            - ($stats['precpu_stats']['system_cpu_usage'] ?? 0);

        $cpuCount = $stats['cpu_stats']['online_cpus']
            ?? count($stats['cpu_stats']['cpu_usage']['percpu_usage'] ?? [])
            ?: 1;

        if ($systemDelta > 0 && $cpuDelta > 0) {
            return ($cpuDelta / $systemDelta) * $cpuCount * 100;
        }

        return null;
    }

    protected function calculateMemoryPercent(array $stats): ?float
    {
        $memoryUsage = $stats['memory_stats']['usage'] ?? 0;
        $memoryLimit = $stats['memory_stats']['limit'] ?? 0;

        if ($memoryLimit > 0) {
            return ($memoryUsage / $memoryLimit) * 100;
        }

        return null;
    }

    protected function getBaseUrl(Node $node): string
    {
        $port = $node->port ?? 2375;
        return "http://{$node->host}:{$port}";
    }
}
