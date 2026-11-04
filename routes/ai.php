<?php

use App\Mcp\Servers\LoggerServer;
use Laravel\Mcp\Facades\Mcp;

// Remote MCP endpoint. Auth: personal access token (php artisan mcp:token) with the "mcp" ability.
Mcp::web('/mcp', LoggerServer::class)
    ->middleware(['auth:sanctum', 'ability:mcp', 'throttle:120,1']);
