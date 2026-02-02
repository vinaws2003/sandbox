<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertLogFactory extends Factory
{
    protected $model = AlertLog::class;

    public function definition(): array
    {
        return [
            'alert_id' => Alert::factory(),
            'node_id' => Node::factory(),
            'metric_value' => $this->faker->randomFloat(4, 0, 100),
            'message' => $this->faker->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function forAlert(Alert $alert): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_id' => $alert->id,
            'node_id' => $alert->node_id,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
