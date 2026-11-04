<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every MCP token from the old profile card goes (Julian, 2026-11-04): tokens are
 * made by `php artisan mcp:token` from now on, with scopes picked per token.
 * Not reversible: the tokens are gone, make new ones with mcp:token.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('personal_access_tokens')->where('abilities', 'like', '%"mcp"%')->delete();
    }

    public function down(): void {}
};
