<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\Metric;
use App\Models\Node;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    public function run(): void
    {
        // Create Synology nodes
        $synologyNodes = collect([
            ['name' => 'NAS Primary', 'host' => '192.168.1.100'],
            ['name' => 'NAS Backup', 'host' => '192.168.1.101'],
            ['name' => 'NAS Media', 'host' => '192.168.1.102'],
        ])->map(fn ($data) => Node::factory()->synology()->create($data));

        // Create Galera cluster nodes
        $galeraNodes = collect([
            ['name' => 'Galera Node 1', 'host' => '192.168.1.110'],
            ['name' => 'Galera Node 2', 'host' => '192.168.1.111'],
            ['name' => 'Galera Node 3', 'host' => '192.168.1.112'],
        ])->map(fn ($data) => Node::factory()->galera()->create($data));

        // Create Laravel app nodes
        $laravelNodes = collect([
            ['name' => 'API Gateway', 'host' => 'api.example.com', 'port' => 443],
            ['name' => 'Admin Panel', 'host' => 'admin.example.com', 'port' => 443],
            ['name' => 'Customer Portal', 'host' => 'portal.example.com', 'port' => 443],
            ['name' => 'Scheduler Service', 'host' => 'scheduler.example.com', 'port' => 443],
            ['name' => 'Queue Worker', 'host' => 'worker.example.com', 'port' => 443],
            ['name' => 'Reporting Service', 'host' => 'reports.example.com', 'port' => 443],
            ['name' => 'Auth Service', 'host' => 'auth.example.com', 'port' => 443],
            ['name' => 'Notification Service', 'host' => 'notify.example.com', 'port' => 443],
        ])->map(fn ($data) => Node::factory()->laravelApp()->create($data));

        // Generate metrics for Synology nodes
        foreach ($synologyNodes as $node) {
            $this->generateSynologyMetrics($node);
        }

        // Generate metrics for Galera nodes
        foreach ($galeraNodes as $node) {
            $this->generateGaleraMetrics($node);
        }

        // Generate metrics for Laravel app nodes
        foreach ($laravelNodes as $node) {
            $this->generateLaravelAppMetrics($node);
        }

        // Create sample alerts
        $this->createSampleAlerts($synologyNodes, $galeraNodes, $laravelNodes);
    }

    protected function generateSynologyMetrics(Node $node): void
    {
        $types = ['cpu', 'memory', 'disk', 'network_in', 'network_out', 'temperature'];

        for ($i = 0; $i < 60; $i++) {
            $recordedAt = Carbon::now()->subMinutes($i);

            foreach ($types as $type) {
                Metric::create([
                    'node_id' => $node->id,
                    'type' => $type,
                    'value' => $this->getRealisticValue($type),
                    'recorded_at' => $recordedAt,
                ]);
            }
        }
    }

    protected function generateGaleraMetrics(Node $node): void
    {
        $types = [
            'wsrep_cluster_size' => 3,
            'wsrep_ready' => 1,
            'wsrep_connected' => 1,
            'wsrep_flow_control_paused' => 0,
        ];

        for ($i = 0; $i < 60; $i++) {
            $recordedAt = Carbon::now()->subMinutes($i);

            foreach ($types as $type => $baseValue) {
                Metric::create([
                    'node_id' => $node->id,
                    'type' => $type,
                    'value' => $baseValue,
                    'recorded_at' => $recordedAt,
                ]);
            }

            // Add wsrep_local_state (should be 4 for Synced)
            Metric::create([
                'node_id' => $node->id,
                'type' => 'wsrep_local_state',
                'value' => 4,
                'metadata' => ['comment' => 'Synced'],
                'recorded_at' => $recordedAt,
            ]);
        }
    }

    protected function generateLaravelAppMetrics(Node $node): void
    {
        $types = ['response_time', 'status', 'queue_size', 'failed_jobs'];

        for ($i = 0; $i < 60; $i++) {
            $recordedAt = Carbon::now()->subMinutes($i);

            Metric::create([
                'node_id' => $node->id,
                'type' => 'response_time',
                'value' => rand(50, 500) / 1000,
                'recorded_at' => $recordedAt,
            ]);

            Metric::create([
                'node_id' => $node->id,
                'type' => 'status',
                'value' => 1, // healthy
                'recorded_at' => $recordedAt,
            ]);

            Metric::create([
                'node_id' => $node->id,
                'type' => 'queue_size',
                'value' => rand(0, 50),
                'recorded_at' => $recordedAt,
            ]);

            Metric::create([
                'node_id' => $node->id,
                'type' => 'failed_jobs',
                'value' => rand(0, 5),
                'recorded_at' => $recordedAt,
            ]);
        }
    }

    protected function getRealisticValue(string $type): float
    {
        return match ($type) {
            'cpu' => rand(1000, 7000) / 100,
            'memory' => rand(4000, 8500) / 100,
            'disk' => rand(3000, 7500) / 100,
            'network_in' => rand(100, 10000) / 100,
            'network_out' => rand(50, 5000) / 100,
            'temperature' => rand(3500, 5500) / 100,
            default => rand(0, 10000) / 100,
        };
    }

    protected function createSampleAlerts($synologyNodes, $galeraNodes, $laravelNodes): void
    {
        // Global CPU alert
        $cpuAlert = Alert::create([
            'node_id' => null,
            'metric_type' => 'cpu',
            'condition' => 'gt',
            'threshold' => 80,
            'notification_channel' => 'database',
            'notification_target' => null,
            'cooldown_minutes' => 15,
        ]);

        // Global memory alert
        Alert::create([
            'node_id' => null,
            'metric_type' => 'memory',
            'condition' => 'gt',
            'threshold' => 90,
            'notification_channel' => 'database',
            'notification_target' => null,
            'cooldown_minutes' => 15,
        ]);

        // Disk alert for primary NAS
        Alert::create([
            'node_id' => $synologyNodes->first()->id,
            'metric_type' => 'disk',
            'condition' => 'gt',
            'threshold' => 85,
            'notification_channel' => 'mail',
            'notification_target' => 'admin@example.com',
            'cooldown_minutes' => 30,
        ]);

        // Galera cluster size alert
        Alert::create([
            'node_id' => $galeraNodes->first()->id,
            'metric_type' => 'wsrep_cluster_size',
            'condition' => 'lt',
            'threshold' => 3,
            'notification_channel' => 'slack',
            'notification_target' => '#alerts',
            'cooldown_minutes' => 5,
        ]);

        // Response time alert for API
        Alert::create([
            'node_id' => $laravelNodes->first()->id,
            'metric_type' => 'response_time',
            'condition' => 'gt',
            'threshold' => 1.0,
            'notification_channel' => 'database',
            'notification_target' => null,
            'cooldown_minutes' => 10,
        ]);

        // Create some sample alert logs
        foreach (range(1, 10) as $i) {
            AlertLog::create([
                'alert_id' => $cpuAlert->id,
                'node_id' => $synologyNodes->random()->id,
                'metric_value' => rand(8000, 9500) / 100,
                'message' => 'CPU usage exceeded threshold of 80%',
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }
    }
}
