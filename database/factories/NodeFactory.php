<?php

namespace Database\Factories;

use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeFactory extends Factory
{
    protected $model = Node::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['synology', 'docker', 'galera', 'laravel_app']);

        return [
            'name' => $this->faker->words(2, true) . ' ' . ucfirst($type),
            'type' => $type,
            'host' => $this->faker->ipv4(),
            'port' => $this->getDefaultPort($type),
            'credentials' => $this->getCredentials($type),
            'is_active' => true,
        ];
    }

    protected function getDefaultPort(string $type): int
    {
        return match ($type) {
            'synology' => 5000,
            'docker' => 2375,
            'galera' => 3306,
            'laravel_app' => 80,
            default => 80,
        };
    }

    protected function getCredentials(string $type): ?array
    {
        return match ($type) {
            'synology' => [
                'username' => 'admin',
                'password' => 'password',
            ],
            'galera' => [
                'username' => 'monitor',
                'password' => 'monitor_password',
                'database' => 'mysql',
            ],
            'laravel_app' => [
                'health_endpoint' => '/health',
            ],
            default => null,
        };
    }

    public function synology(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'synology',
            'port' => 5000,
            'credentials' => [
                'username' => 'admin',
                'password' => 'password',
            ],
        ]);
    }

    public function docker(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'docker',
            'port' => 2375,
            'credentials' => null,
        ]);
    }

    public function galera(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'galera',
            'port' => 3306,
            'credentials' => [
                'username' => 'monitor',
                'password' => 'monitor_password',
                'database' => 'mysql',
            ],
        ]);
    }

    public function laravelApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'laravel_app',
            'port' => 80,
            'credentials' => [
                'health_endpoint' => '/health',
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
