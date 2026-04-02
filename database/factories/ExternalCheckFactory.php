<?php

namespace Database\Factories;

use App\Models\ExternalCheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExternalCheck>
 */
class ExternalCheckFactory extends Factory
{
    protected $model = ExternalCheck::class;

    public function definition(): array
    {
        $plainTextToken = Str::random(40);

        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->sentence(),
            'enabled' => true,
            'token_hash' => hash('sha256', $plainTextToken),
            'encrypted_token' => Crypt::encryptString($plainTextToken),
            'token_last_eight' => substr($plainTextToken, -8),
            'min_level' => 'error',
            'time_window_minutes' => 60,
            'count_threshold' => 5,
            'group_by' => ExternalCheck::DEFAULT_GROUP_BY,
            'group_across_projects' => false,
            'selector_tags' => ['production'],
            'included_project_ids' => [],
            'excluded_project_ids' => [],
            'memory_percent_gte' => null,
            'disk_percent_gte' => null,
            'token_generated_at' => now(),
        ];
    }

    public function withToken(string $plainTextToken): static
    {
        return $this->state(fn () => [
            'token_hash' => hash('sha256', $plainTextToken),
            'encrypted_token' => Crypt::encryptString($plainTextToken),
            'token_last_eight' => substr($plainTextToken, -8),
            'token_generated_at' => now(),
        ]);
    }
}
