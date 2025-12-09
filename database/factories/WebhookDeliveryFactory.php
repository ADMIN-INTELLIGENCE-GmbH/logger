<?php

namespace Database\Factories;

use App\Models\Log;
use App\Models\Project;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'log_id' => Log::factory(),
            'url' => fake()->url(),
            'event_type' => 'log',
            'payload' => [
                'text' => fake()->sentence(),
                'attachments' => [],
            ],
            'status_code' => 200,
            'response_body' => 'ok',
            'error_message' => null,
            'success' => true,
            'attempt' => 1,
            'delivered_at' => now(),
        ];
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => 500,
            'response_body' => 'Internal Server Error',
            'success' => false,
            'error_message' => 'Server error',
        ]);
    }

    /**
     * Indicate that this is a test webhook.
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_id' => null,
            'event_type' => 'test',
        ]);
    }
}
