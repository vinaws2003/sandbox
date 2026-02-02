<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'metric_type' => $this->faker->randomElement(['cpu', 'memory', 'disk', 'response_time']),
            'condition' => $this->faker->randomElement(['gt', 'gte', 'lt', 'lte']),
            'threshold' => $this->faker->randomFloat(2, 50, 95),
            'notification_channel' => $this->faker->randomElement(['mail', 'slack', 'database']),
            'notification_target' => $this->faker->email(),
            'is_active' => true,
            'cooldown_minutes' => 15,
            'last_triggered_at' => null,
        ];
    }

    public function cpuAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'cpu',
            'condition' => 'gt',
            'threshold' => 80,
        ]);
    }

    public function memoryAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'memory',
            'condition' => 'gt',
            'threshold' => 90,
        ]);
    }

    public function diskAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'disk',
            'condition' => 'gt',
            'threshold' => 85,
        ]);
    }

    public function responseTimeAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'response_time',
            'condition' => 'gt',
            'threshold' => 1.0,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'node_id' => null,
        ]);
    }

    public function forNode(Node $node): static
    {
        return $this->state(fn (array $attributes) => [
            'node_id' => $node->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function recentlyTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => now()->subMinutes(5),
        ]);
    }
}
