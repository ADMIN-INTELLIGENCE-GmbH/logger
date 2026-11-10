<?php

namespace App\Mcp\Concerns;

use App\Mcp\TokenScopes;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\AccessToken;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * A tool the token was not given is neither listed nor callable: laravel/mcp
 * resolves tools/call through the same shouldRegister() filter.
 *
 * OAuth tokens have no per-tool scopes; LoggerServer::boot() limits KIRA's to
 * the read-only tools.
 */
trait ScopedByToken
{
    public function shouldRegister(): bool
    {
        $user = Auth::user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;

        if ($token instanceof AccessToken) {
            return true;
        }

        return $token instanceof HasAbilities && TokenScopes::allows($token, $this->name());
    }
}
