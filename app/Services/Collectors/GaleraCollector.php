<?php

namespace App\Services\Collectors;

use App\Contracts\CollectorInterface;
use App\Exceptions\ConnectionException;
use App\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class GaleraCollector implements CollectorInterface
{
    public static function supports(): array
    {
        return ['galera'];
    }

    public function testConnection(Node $node): bool
    {
        try {
            $pdo = $this->connect($node);
            $pdo = null;
            return true;
        } catch (ConnectionException $e) {
            Log::warning('Galera connection test failed', [
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
            $pdo = $this->connect($node);

            $wsrepVars = $this->getWsrepVariables($pdo);

            // Cluster size
            if (isset($wsrepVars['wsrep_cluster_size'])) {
                $metrics->push([
                    'type' => 'wsrep_cluster_size',
                    'value' => (float) $wsrepVars['wsrep_cluster_size'],
                ]);
            }

            // Cluster status (Primary = 1, Non-Primary = 0)
            if (isset($wsrepVars['wsrep_cluster_status'])) {
                $metrics->push([
                    'type' => 'wsrep_cluster_status',
                    'value' => $wsrepVars['wsrep_cluster_status'] === 'Primary' ? 1 : 0,
                    'metadata' => ['status' => $wsrepVars['wsrep_cluster_status']],
                ]);
            }

            // Ready status
            if (isset($wsrepVars['wsrep_ready'])) {
                $metrics->push([
                    'type' => 'wsrep_ready',
                    'value' => $wsrepVars['wsrep_ready'] === 'ON' ? 1 : 0,
                ]);
            }

            // Connected status
            if (isset($wsrepVars['wsrep_connected'])) {
                $metrics->push([
                    'type' => 'wsrep_connected',
                    'value' => $wsrepVars['wsrep_connected'] === 'ON' ? 1 : 0,
                ]);
            }

            // Local state
            if (isset($wsrepVars['wsrep_local_state'])) {
                $metrics->push([
                    'type' => 'wsrep_local_state',
                    'value' => (float) $wsrepVars['wsrep_local_state'],
                    'metadata' => [
                        'comment' => $wsrepVars['wsrep_local_state_comment'] ?? null,
                    ],
                ]);
            }

            // Flow control paused ratio
            if (isset($wsrepVars['wsrep_flow_control_paused'])) {
                $metrics->push([
                    'type' => 'wsrep_flow_control_paused',
                    'value' => (float) $wsrepVars['wsrep_flow_control_paused'],
                ]);
            }

            // Additional useful metrics
            if (isset($wsrepVars['wsrep_local_recv_queue_avg'])) {
                $metrics->push([
                    'type' => 'wsrep_recv_queue_avg',
                    'value' => (float) $wsrepVars['wsrep_local_recv_queue_avg'],
                ]);
            }

            if (isset($wsrepVars['wsrep_local_send_queue_avg'])) {
                $metrics->push([
                    'type' => 'wsrep_send_queue_avg',
                    'value' => (float) $wsrepVars['wsrep_local_send_queue_avg'],
                ]);
            }

            $pdo = null;

        } catch (ConnectionException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Galera collection failed', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            throw ConnectionException::apiError($node->host, 'galera', $e->getMessage());
        }

        return $metrics;
    }

    protected function connect(Node $node): PDO
    {
        $credentials = $node->credentials;
        $host = $node->host;
        $port = $node->port ?? 3306;
        $database = $credentials['database'] ?? 'mysql';
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database}";

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_TIMEOUT => config('monitor.galera.timeout'),
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Access denied')) {
                throw ConnectionException::authFailed($host, 'galera');
            }

            if (str_contains($e->getMessage(), 'Connection refused') ||
                str_contains($e->getMessage(), 'No such host')) {
                throw ConnectionException::refused($host, 'galera');
            }

            if (str_contains($e->getMessage(), 'timed out')) {
                throw ConnectionException::timeout($host, 'galera');
            }

            throw new ConnectionException($e->getMessage(), 0, $e, 'galera', $host);
        }
    }

    protected function getWsrepVariables(PDO $pdo): array
    {
        $statement = $pdo->query("SHOW GLOBAL STATUS LIKE 'wsrep_%'");
        $results = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

        return $results ?: [];
    }
}
