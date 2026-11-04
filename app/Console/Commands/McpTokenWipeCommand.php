<?php

namespace App\Console\Commands;

use App\Mcp\TokenScopes;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class McpTokenWipeCommand extends Command
{
    protected $signature = 'mcp:token:wipe
                            {user : User id or email}
                            {--force : Delete without asking}';

    protected $description = "Delete a user's MCP API tokens";

    public function handle(): int
    {
        $user = User::where(ctype_digit($this->argument('user')) ? 'id' : 'email', $this->argument('user'))->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $tokens = $user->tokens()->get()->filter(fn ($t) => in_array('mcp', (array) $t->abilities, true));

        if ($tokens->isEmpty()) {
            $this->info("{$user->email} has no MCP tokens.");

            return self::SUCCESS;
        }

        $this->table(['Name', 'Scopes', 'Last used', 'Expires'], $tokens->map(fn ($t) => [
            $t->name,
            implode(', ', TokenScopes::of($t) ?? ['all tools']),
            $t->last_used_at?->format('Y-m-d H:i') ?? 'never',
            $t->expires_at?->format('Y-m-d') ?? 'never',
        ])->all());

        if (! $this->option('force') && ! confirm("Delete these {$tokens->count()} tokens of {$user->email}?", default: false)) {
            $this->info('Nothing deleted.');

            return self::SUCCESS;
        }

        $deleted = $user->tokens()->whereKey($tokens->modelKeys())->delete();
        $this->info("Deleted {$deleted} MCP token(s).");

        return self::SUCCESS;
    }
}
