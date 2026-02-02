<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\Node;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricService
{
    public function getLatestMetrics(Node $node): Collection
    {
        return Metric::query()
            ->where('node_id', $node->id)
            ->where('recorded_at', '>=', now()->subMinutes(5))
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('type');
    }

    public function getLatestMetricValue(Node $node, string $type): ?float
    {
        $metric = Metric::query()
            ->where('node_id', $node->id)
            ->where('type', $type)
            ->orderByDesc('recorded_at')
            ->first();

        return $metric?->value;
    }

    public function getMetricsForTimeRange(
        Node $node,
        string $type,
        Carbon $from,
        Carbon $to,
        ?string $aggregation = null
    ): Collection {
        $query = Metric::query()
            ->where('node_id', $node->id)
            ->where('type', $type)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at');

        if ($aggregation) {
            return $this->aggregateMetrics($query, $from, $to, $aggregation);
        }

        return $query->get();
    }

    public function getAggregatedStats(
        Node $node,
        string $type,
        Carbon $from,
        Carbon $to
    ): array {
        $stats = Metric::query()
            ->where('node_id', $node->id)
            ->where('type', $type)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('
                AVG(value) as avg,
                MIN(value) as min,
                MAX(value) as max,
                COUNT(*) as count
            ')
            ->first();

        return [
            'avg' => round($stats->avg ?? 0, 2),
            'min' => round($stats->min ?? 0, 2),
            'max' => round($stats->max ?? 0, 2),
            'count' => $stats->count ?? 0,
        ];
    }

    public function getChartData(
        Node $node,
        string $type,
        string $range = '1h'
    ): array {
        [$from, $interval] = $this->parseRange($range);

        $metrics = Metric::query()
            ->where('node_id', $node->id)
            ->where('type', $type)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get();

        // Group by interval and calculate averages
        $grouped = $metrics->groupBy(function ($metric) use ($interval) {
            return $metric->recorded_at->startOf($interval)->toIso8601String();
        });

        $labels = [];
        $values = [];

        foreach ($grouped as $timestamp => $group) {
            $labels[] = Carbon::parse($timestamp)->format('H:i');
            $values[] = round($group->avg('value'), 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'stats' => $this->getAggregatedStats($node, $type, $from, now()),
        ];
    }

    protected function parseRange(string $range): array
    {
        return match ($range) {
            '1h' => [now()->subHour(), 'minute'],
            '6h' => [now()->subHours(6), 'minute'],
            '24h' => [now()->subDay(), 'hour'],
            '7d' => [now()->subDays(7), 'hour'],
            '30d' => [now()->subDays(30), 'day'],
            default => [now()->subHour(), 'minute'],
        };
    }

    protected function aggregateMetrics($query, Carbon $from, Carbon $to, string $aggregation): Collection
    {
        $interval = match ($aggregation) {
            'minute' => "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i:00')",
            'hour' => "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE(recorded_at)",
            default => "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i:00')",
        };

        return $query->selectRaw("
            {$interval} as period,
            AVG(value) as value,
            MIN(value) as min_value,
            MAX(value) as max_value,
            COUNT(*) as sample_count
        ")
            ->groupByRaw($interval)
            ->get();
    }

    public function getAllNodesLatestMetrics(): Collection
    {
        return Node::query()
            ->active()
            ->with(['metrics' => function ($query) {
                $query->where('recorded_at', '>=', now()->subMinutes(5))
                    ->orderByDesc('recorded_at');
            }])
            ->get()
            ->map(function (Node $node) {
                $latestMetrics = $node->metrics->unique('type');

                return [
                    'node' => $node,
                    'metrics' => $latestMetrics->keyBy('type'),
                    'last_updated' => $latestMetrics->max('recorded_at'),
                ];
            });
    }

    public function cleanup(int $retentionDays): int
    {
        $cutoff = now()->subDays($retentionDays);
        $deleted = 0;

        // Delete in chunks to avoid memory issues
        do {
            $count = Metric::query()
                ->where('recorded_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $deleted += $count;
        } while ($count > 0);

        return $deleted;
    }
}
