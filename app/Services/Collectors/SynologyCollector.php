<?php

namespace App\Services\Collectors;

use App\Contracts\CollectorInterface;
use App\Exceptions\ConnectionException;
use App\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SynologyCollector implements CollectorInterface
{
    protected ?string $sid = null;

    public static function supports(): array
    {
        return ['synology'];
    }

    public function testConnection(Node $node): bool
    {
        try {
            $this->authenticate($node);
            $this->logout($node);
            return true;
        } catch (ConnectionException $e) {
            Log::warning('Synology connection test failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function collect(Node $node): Collection
    {
        $metrics = collect();

        try {
            $this->authenticate($node);

            $systemInfo = $this->getSystemInfo($node);
            $utilization = $this->getUtilization($node);

            if ($utilization) {
                if (isset($utilization['cpu']['user_load'])) {
                    $metrics->push([
                        'type' => 'cpu',
                        'value' => $utilization['cpu']['user_load'] + ($utilization['cpu']['system_load'] ?? 0),
                    ]);
                }

                if (isset($utilization['memory']['real_usage'])) {
                    $metrics->push([
                        'type' => 'memory',
                        'value' => $utilization['memory']['real_usage'],
                    ]);
                }

                if (isset($utilization['network'])) {
                    $totalRx = 0;
                    $totalTx = 0;
                    foreach ($utilization['network'] as $iface) {
                        $totalRx += $iface['rx'] ?? 0;
                        $totalTx += $iface['tx'] ?? 0;
                    }
                    $metrics->push(['type' => 'network_in', 'value' => $totalRx / 1024]);
                    $metrics->push(['type' => 'network_out', 'value' => $totalTx / 1024]);
                }
            }

            if ($systemInfo) {
                if (isset($systemInfo['temperature'])) {
                    $metrics->push([
                        'type' => 'temperature',
                        'value' => $systemInfo['temperature'],
                    ]);
                }

                if (isset($systemInfo['uptime'])) {
                    $metrics->push([
                        'type' => 'uptime',
                        'value' => $systemInfo['uptime'],
                    ]);
                }
            }

            $storageInfo = $this->getStorageInfo($node);
            if ($storageInfo && isset($storageInfo['volumes'])) {
                foreach ($storageInfo['volumes'] as $volume) {
                    if (isset($volume['size']['used'], $volume['size']['total']) && $volume['size']['total'] > 0) {
                        $usedPercent = ($volume['size']['used'] / $volume['size']['total']) * 100;
                        $metrics->push([
                            'type' => 'disk',
                            'value' => $usedPercent,
                            'metadata' => ['volume' => $volume['id'] ?? 'unknown'],
                        ]);
                    }
                }
            }

            $this->logout($node);

        } catch (ConnectionException $e) {
            Log::error('Synology collection failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $metrics;
    }

    protected function authenticate(Node $node): void
    {
        $credentials = $node->credentials;
        $baseUrl = $this->getBaseUrl($node);

        $response = Http::timeout(config('monitor.synology.timeout'))
            ->get("{$baseUrl}/webapi/auth.cgi", [
                'api' => 'SYNO.API.Auth',
                'version' => config('monitor.synology.api_version'),
                'method' => 'login',
                'account' => $credentials['username'] ?? '',
                'passwd' => $credentials['password'] ?? '',
                'format' => 'sid',
            ]);

        if (!$response->successful()) {
            throw ConnectionException::refused($node->host, 'synology');
        }

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            throw ConnectionException::authFailed($node->host, 'synology');
        }

        $this->sid = $data['data']['sid'] ?? null;
    }

    protected function logout(Node $node): void
    {
        if (!$this->sid) {
            return;
        }

        $baseUrl = $this->getBaseUrl($node);

        Http::timeout(5)->get("{$baseUrl}/webapi/auth.cgi", [
            'api' => 'SYNO.API.Auth',
            'version' => config('monitor.synology.api_version'),
            'method' => 'logout',
            '_sid' => $this->sid,
        ]);

        $this->sid = null;
    }

    protected function getSystemInfo(Node $node): ?array
    {
        $baseUrl = $this->getBaseUrl($node);

        $response = Http::timeout(config('monitor.synology.timeout'))
            ->get("{$baseUrl}/webapi/entry.cgi", [
                'api' => 'SYNO.Core.System',
                'version' => 1,
                'method' => 'info',
                '_sid' => $this->sid,
            ]);

        if ($response->successful() && ($response->json('success') ?? false)) {
            return $response->json('data');
        }

        return null;
    }

    protected function getUtilization(Node $node): ?array
    {
        $baseUrl = $this->getBaseUrl($node);

        $response = Http::timeout(config('monitor.synology.timeout'))
            ->get("{$baseUrl}/webapi/entry.cgi", [
                'api' => 'SYNO.Core.System.Utilization',
                'version' => 1,
                'method' => 'get',
                '_sid' => $this->sid,
            ]);

        if ($response->successful() && ($response->json('success') ?? false)) {
            return $response->json('data');
        }

        return null;
    }

    protected function getStorageInfo(Node $node): ?array
    {
        $baseUrl = $this->getBaseUrl($node);

        $response = Http::timeout(config('monitor.synology.timeout'))
            ->get("{$baseUrl}/webapi/entry.cgi", [
                'api' => 'SYNO.Storage.CGI.Storage',
                'version' => 1,
                'method' => 'load_info',
                '_sid' => $this->sid,
            ]);

        if ($response->successful() && ($response->json('success') ?? false)) {
            return $response->json('data');
        }

        return null;
    }

    protected function getBaseUrl(Node $node): string
    {
        $port = $node->port ?? 5000;
        $protocol = $port === 5001 ? 'https' : 'http';

        return "{$protocol}://{$node->host}:{$port}";
    }
}
