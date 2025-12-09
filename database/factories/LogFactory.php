<?php

namespace Database\Factories;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Log>
 */
class LogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Log::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'level' => fake()->randomElement(Log::LEVELS),
            'channel' => fake()->randomElement(['stack', 'single', 'daily', 'slack', 'syslog', 'errorlog']),
            'message' => fake()->sentence(),
            'context' => $this->generateContext(),
            'extra' => $this->generateExtra(),
            'controller' => fake()->optional(0.7)->randomElement([
                'App\\Http\\Controllers\\UserController',
                'App\\Http\\Controllers\\OrderController',
                'App\\Http\\Controllers\\PaymentController',
                'App\\Http\\Controllers\\ProductController',
                'App\\Http\\Controllers\\AuthController',
            ]),
            'route_name' => fake()->optional(0.6)->randomElement([
                'users.index', 'users.show', 'users.store',
                'orders.index', 'orders.show', 'orders.create',
                'products.index', 'products.show',
                'auth.login', 'auth.logout', 'auth.register',
            ]),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'request_url' => fake()->optional(0.8)->url(),
            'user_id' => fake()->optional(0.8)->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->optional(0.9)->userAgent(),
            'app_env' => fake()->randomElement(['local', 'staging', 'production']),
            'app_debug' => fake()->boolean(30),
            'referrer' => fake()->optional(0.5)->url(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Generate realistic extra data (Monolog processor data).
     */
    protected function generateExtra(): array
    {
        $extra = [];

        if (fake()->boolean(60)) {
            $extra['memory_usage'] = fake()->numberBetween(10000000, 100000000);
            $extra['memory_peak_usage'] = fake()->numberBetween(15000000, 150000000);
        }

        if (fake()->boolean(40)) {
            $extra['process_id'] = fake()->numberBetween(1000, 99999);
        }

        if (fake()->boolean(30)) {
            $extra['uid'] = fake()->uuid();
        }

        return $extra;
    }

    /**
     * Generate realistic context data.
     */
    protected function generateContext(): array
    {
        $context = [
            'file' => fake()->randomElement([
                '/app/Http/Controllers/UserController.php',
                '/app/Services/PaymentService.php',
                '/app/Models/Order.php',
            ]),
            'line' => fake()->numberBetween(10, 500),
        ];

        // Add stack trace for errors
        if (fake()->boolean(70)) {
            $context['trace'] = [
                [
                    'file' => '/app/Http/Controllers/UserController.php',
                    'line' => fake()->numberBetween(10, 100),
                    'function' => 'store',
                    'class' => 'App\\Http\\Controllers\\UserController',
                ],
                [
                    'file' => '/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',
                    'line' => fake()->numberBetween(10, 100),
                    'function' => 'callAction',
                    'class' => 'Illuminate\\Routing\\Controller',
                ],
            ];
        }

        // Add request data (sanitized)
        if (fake()->boolean(50)) {
            $context['request'] = [
                'url' => fake()->url(),
                'input' => [
                    'email' => '***sanitized***',
                    'name' => fake()->name(),
                ],
            ];
        }

        return $context;
    }

    /**
     * Indicate that the log is an error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'error',
            'message' => fake()->randomElement([
                'Database connection failed',
                'Invalid user credentials',
                'Payment processing error',
                'Undefined index: user_id',
                'Class not found: App\\Services\\OldService',
            ]),
        ]);
    }

    /**
     * Indicate that the log is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'critical',
            'message' => fake()->randomElement([
                'System out of memory',
                'Database server unreachable',
                'Payment gateway timeout',
                'Redis connection lost',
            ]),
        ]);
    }

    /**
     * Indicate that the log is info level.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'info',
            'message' => fake()->randomElement([
                'User logged in successfully',
                'Order created',
                'Payment processed',
                'Email sent',
            ]),
        ]);
    }

    /**
     * Indicate that the log is debug level.
     */
    public function debug(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'debug',
            'message' => fake()->randomElement([
                'Query executed in 0.023s',
                'Cache hit for key: users.1',
                'Request received',
                'Response sent',
            ]),
        ]);
    }

    /**
     * Create a log for a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }
}
