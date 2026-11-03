<?php

namespace App\Mcp\Concerns;

use App\Mcp\TokenScopes;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * A tool the token was not given is neither listed nor callable: laravel/mcp
 * resolves tools/call through the same shouldRegister() filter.
 */
trait ScopedByToken
{
    public function shouldRegister(): bool
    {
        $user = Auth::user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;

        return $token instanceof HasAbilities && TokenScopes::allows($token, $this->name());
    }
}
