<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WipeMcpTokensMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_deletes_mcp_tokens_only(): void
    {
        $user = User::factory()->create();
        $user->createToken('legacy', ['mcp']);
        $user->createToken('scoped', ['mcp', 'mcp:read']);
        $user->createToken('other', ['other']);

        (require database_path('migrations/2026_11_04_000001_wipe_mcp_tokens.php'))->up();

        $this->assertSame(['other'], $user->tokens()->pluck('name')->all());
    }
}
