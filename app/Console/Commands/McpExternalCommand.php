<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class McpExternalCommand extends Command
{
    protected $signature = 'mcp:external
                            {user : User id or email}
                            {--revoke : Take the permission away}';

    protected $description = 'Grant or revoke mcp.external: MCP with own tokens and OAuth clients other than KIRA';

    public function handle(): int
    {
        $user = User::where(ctype_digit($this->argument('user')) ? 'id' : 'email', $this->argument('user'))->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $user->forceFill(['mcp_external' => ! $this->option('revoke')])->save();
        $this->info("{$user->email}: mcp.external ".($user->mcp_external ? 'granted' : 'revoked').'.');

        return self::SUCCESS;
    }
}
