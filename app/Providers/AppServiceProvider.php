<?php

namespace App\Providers;

use App\Events\LogCreated;
use App\Listeners\WebhookDispatcher;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);

        Event::listen(
            LogCreated::class,
            WebhookDispatcher::class,
        );
    }
}
