<?php

namespace Database\Factories;

use App\Models\Metric;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    protected $model = Metric::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'type' => $this->faker->randomElement([
                'cpu', 'memory', 'disk', 'network_in', 'network_out',
                'temperature', 'uptime', 'response_time',
            ]),
            'value' => $this->faker->randomFloat(4, 0, 100),
            'metadata' => null,
            'recorded_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function cpu(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cpu',
            'value' => $this->faker->randomFloat(2, 0, 100),
        ]);
    }

    public function memory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'memory',
            'value' => $this->faker->randomFloat(2, 20, 95),
        ]);
    }

    public function disk(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'disk',
            'value' => $this->faker->randomFloat(2, 10, 90),
        ]);
    }

    public function responseTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'response_time',
            'value' => $this->faker->randomFloat(4, 0.05, 2.0),
        ]);
    }

    public function galeraClusterSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'wsrep_cluster_size',
            'value' => 3,
        ]);
    }

    public function forNode(Node $node): static
    {
        return $this->state(fn (array $attributes) => [
            'node_id' => $node->id,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
