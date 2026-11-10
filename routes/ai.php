<?php

use App\Http\Middleware\AuthorizeMcpToken;
use App\Mcp\Servers\LoggerServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth (Passport): discovery, /oauth/authorize behind the web login, /oauth/token, /oauth/register.
Mcp::oauthRoutes();

// Remote MCP endpoint. Auth: an OAuth token (KIRA: read-only tools), or for users
// with mcp.external a personal token (php artisan mcp:token) or another OAuth client.
Mcp::web('/mcp', LoggerServer::class)
    ->middleware(['auth:sanctum,api', AuthorizeMcpToken::class, 'throttle:120,1']);
