<?php

namespace App\Http\Middleware;

use App\Mcp\TokenScopes;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Who may talk to /mcp: KIRA's OAuth tokens always (read-only tools, see
 * LoggerServer::boot()). Everything else (own Sanctum tokens with the "mcp"
 * ability, other OAuth clients) only for users with mcp.external.
 */
class AuthorizeMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;

        if ($token instanceof AccessToken) {
            abort_unless($token->can(TokenScopes::OAUTH_READ) || $token->can(Registrar::OAUTH_SCOPE), 403, 'Token has no MCP scope.');

            if (TokenScopes::isKira($token)) {
                return $next($request);
            }
        } else {
            abort_unless($user instanceof User && $user->tokenCan('mcp'), 403, 'Token has no MCP ability.');
        }

        abort_unless($user->mcp_external, 403, 'Only KIRA may use MCP for this account (mcp.external not granted).');

        return $next($request);
    }
}
