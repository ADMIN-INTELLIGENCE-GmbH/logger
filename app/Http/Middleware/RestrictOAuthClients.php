<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the OAuth login of a client other than KIRA for a user without
 * mcp.external, before consent, instead of a 403 from /mcp later. Runs in the
 * web group, after the session; acts only on Passport's authorize page.
 */
class RestrictOAuthClients
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('passport.authorizations.authorize')) {
            return $next($request);
        }

        $user = $request->user();
        $kira = config('services.kira.client_id');

        if ($user instanceof User && ! $user->mcp_external
            && ! (filled($kira) && $request->query('client_id') === (string) $kira)) {
            abort(403, 'This application may not connect to Logger with your account. Only KIRA may. Ask an admin if you need another client.');
        }

        return $next($request);
    }
}
