<?php

namespace App\Providers;

use App\Events\LogCreated;
use App\Listeners\WebhookDispatcher;
use App\Mcp\TokenScopes;
use App\Models\ExternalCheck;
use App\Models\Project;
use App\Policies\ExternalCheckPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;

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
        Gate::policy(ExternalCheck::class, ExternalCheckPolicy::class);

        Event::listen(
            LogCreated::class,
            WebhookDispatcher::class,
        );

        // OAuth for MCP clients (KIRA): short access tokens, refresh tokens
        // rotate on every use. A client that names no scope gets mcp:use.
        Passport::tokensCan([
            TokenScopes::OAUTH_READ => 'Read logs (read-only MCP tools)',
            Registrar::OAUTH_SCOPE => 'Use the MCP server',
        ]);
        Passport::setDefaultScope([Registrar::OAUTH_SCOPE]);
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::$revokeRefreshTokenAfterUse = true;
        Passport::authorizationView('auth.oauth-authorize');
    }
}
