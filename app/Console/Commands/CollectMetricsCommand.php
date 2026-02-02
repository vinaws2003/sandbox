<?php

namespace App\Console\Commands;

use App\Exceptions\ConnectionException;
use App\Models\Metric;
use App\Models\Node;
use App\Providers\MonitorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CollectMetricsCommand extends Command
{
    protected $signature = 'monitor:collect
                            {--node= : Collect only for a specific node ID}
                            {--type= : Collect only for a specific node type}';

    protected $description = 'Collect metrics from all active nodes';

    public function handle(): int
    {
        $this->info('Starting metric collection...');

        $query = Node::query()->active();

        if ($nodeId = $this->option('node')) {
            $query->where('id', $nodeId);
        }

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        $nodes = $query->get();

        if ($nodes->isEmpty()) {
            $this->warn('No active nodes found.');
            return self::SUCCESS;
        }

        $this->info("Found {$nodes->count()} node(s) to collect from.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($nodes as $node) {
            $this->line("  Collecting from {$node->name} ({$node->type})...");

            try {
                $collector = MonitorServiceProvider::getCollectorFor($node->type);

                if (!$collector) {
                    $this->warn("    No collector found for type: {$node->type}");
                    $failureCount++;
                    continue;
                }

                $metrics = $collector->collect($node);

                if ($metrics->isEmpty()) {
                    $this->warn("    No metrics collected.");
                    continue;
                }

                // Bulk insert metrics
                $now = now();
                $metricsData = $metrics->map(fn ($m) => [
                    'node_id' => $node->id,
                    'type' => $m['type'],
                    'value' => $m['value'],
                    'metadata' => isset($m['metadata']) ? json_encode($m['metadata']) : null,
                    'recorded_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                DB::transaction(function () use ($metricsData) {
                    Metric::insert($metricsData);
                });

                $this->info("    Collected {$metrics->count()} metric(s).");
                $successCount++;

            } catch (ConnectionException $e) {
                $this->error("    Connection failed: {$e->getMessage()}");
                Log::error('Metric collection failed', [
                    'node' => $node->name,
                    'node_id' => $node->id,
                    'error' => $e->getMessage(),
                ]);
                $failureCount++;
            } catch (\Exception $e) {
                $this->error("    Error: {$e->getMessage()}");
                Log::error('Metric collection failed', [
                    'node' => $node->name,
                    'node_id' => $node->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("Collection complete: {$successCount} successful, {$failureCount} failed.");

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
