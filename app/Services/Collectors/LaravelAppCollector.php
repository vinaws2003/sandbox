<?php

namespace App\Services\Collectors;

use App\Contracts\CollectorInterface;
use App\Exceptions\ConnectionException;
use App\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LaravelAppCollector implements CollectorInterface
{
    public static function supports(): array
    {
        return ['laravel_app'];
    }

    public function testConnection(Node $node): bool
    {
        try {
            $url = $this->getHealthUrl($node);

            $response = $this->buildRequest($node)->get($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Laravel app connection test failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function collect(Node $node): Collection
    {
        $metrics = collect();
        $url = $this->getHealthUrl($node);
        $retries = config('monitor.laravel_app.retries', 2);

        $startTime = microtime(true);
        $response = null;
        $lastError = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $response = $this->buildRequest($node)->get($url);

                if ($response->successful()) {
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                Log::debug('Laravel app health check attempt failed', [
                    'node' => $node->name,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $retries) {
                    usleep(500000); // 500ms delay between retries
                }
            }
        }

        $responseTime = (microtime(true) - $startTime);

        // Response time metric
        $metrics->push([
            'type' => 'response_time',
            'value' => $responseTime,
        ]);

        if ($response && $response->successful()) {
            $data = $response->json();

            // Status (1 = healthy, 0.5 = degraded, 0 = unhealthy)
            $status = match ($data['status'] ?? 'unknown') {
                'healthy' => 1,
                'degraded' => 0.5,
                default => 0,
            };

            $metrics->push([
                'type' => 'status',
                'value' => $status,
                'metadata' => [
                    'status_text' => $data['status'] ?? 'unknown',
                    'recent_errors' => $data['recent_errors'] ?? [],
                ],
            ]);

            // Parse checks
            $checks = $data['checks'] ?? [];

            // Database connection
            if (isset($checks['database'])) {
                $metrics->push([
                    'type' => 'database_connected',
                    'value' => $checks['database'] === true ? 1 : 0,
                ]);
            }

            // Cache connection
            if (isset($checks['cache'])) {
                $metrics->push([
                    'type' => 'cache_connected',
                    'value' => $checks['cache'] === true ? 1 : 0,
                ]);
            }

            // Queue metrics
            if (isset($checks['queue'])) {
                if (isset($checks['queue']['size'])) {
                    $metrics->push([
                        'type' => 'queue_size',
                        'value' => (float) $checks['queue']['size'],
                    ]);
                }

                if (isset($checks['queue']['failed'])) {
                    $metrics->push([
                        'type' => 'failed_jobs',
                        'value' => (float) $checks['queue']['failed'],
                    ]);
                }
            }

            // Custom response time from health endpoint
            if (isset($data['response_time_ms'])) {
                $metrics->push([
                    'type' => 'internal_response_time',
                    'value' => $data['response_time_ms'] / 1000,
                ]);
            }
        } else {
            // Node is unreachable
            $metrics->push([
                'type' => 'status',
                'value' => 0,
                'metadata' => [
                    'status_text' => 'unreachable',
                    'error' => $lastError?->getMessage() ?? 'Unknown error',
                ],
            ]);

            Log::warning('Laravel app unreachable', [
                'node' => $node->name,
                'url' => $url,
                'error' => $lastError?->getMessage(),
            ]);
        }

        return $metrics;
    }

    protected function buildRequest(Node $node): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout(config('monitor.laravel_app.timeout'));

        $token = $node->credentials['health_token'] ?? null;
        if ($token) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    protected function getHealthUrl(Node $node): string
    {
        $credentials = $node->credentials;
        $endpoint = $credentials['health_endpoint'] ?? '/health';
        $port = $node->port ?? 80;
        $protocol = $port === 443 ? 'https' : 'http';

        $portSuffix = in_array($port, [80, 443]) ? '' : ":{$port}";

        return "{$protocol}://{$node->host}{$portSuffix}{$endpoint}";
    }
}
