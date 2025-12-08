<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['App', 'API', 'Service', 'Platform']),
            'magic_key' => Str::random(64),
            'retention_days' => fake()->randomElement([7, 14, 30, 90, -1]),
            'webhook_url' => fake()->optional(0.3)->url(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the project is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the project has infinite retention.
     */
    public function infiniteRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_days' => -1,
        ]);
    }

    /**
     * Indicate that the project has a webhook configured.
     */
    public function withWebhook(string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_url' => $url ?? fake()->url(),
        ]);
    }
}
