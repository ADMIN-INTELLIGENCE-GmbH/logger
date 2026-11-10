<?php

namespace App\Console\Commands;

use App\Mcp\TokenScopes;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * MCP tokens are made here by an admin on the server; there is no token page
 * in the UI. Scopes are capped at the user's rights, as TokenScopes decides.
 */
class McpTokenCommand extends Command
{
    protected $signature = 'mcp:token
                            {--user= : User id or email}
                            {--name= : Token name (default "Claude")}
                            {--scopes= : Comma-separated scopes, or "all" for every scope the user may have}
                            {--days= : Expiry in days: 30, 90, 365, or 0 for never}';

    protected $description = 'Create an MCP API token for a user and print it once';

    public function handle(): int
    {
        $user = $this->option('user') !== null
            ? User::where(ctype_digit((string) $this->option('user')) ? 'id' : 'email', $this->option('user'))->first()
            : User::find(search(
                label: 'User',
                placeholder: 'Name or email',
                options: fn (string $q) => User::query()
                    ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orderBy('name')->limit(20)->get()
                    ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} <{$u->email}>"])->all(),
            ));

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?? text(label: 'Token name', default: 'Claude', required: true);

        $days = $this->option('days') ?? select(
            label: 'Expires',
            options: ['30' => '30 days', '90' => '90 days', '365' => '1 year', '0' => 'Never'],
            default: '90',
        );

        if (! in_array((string) $days, ['30', '90', '365', '0'], true)) {
            $this->error('--days must be 30, 90, 365 or 0.');

            return self::FAILURE;
        }

        $offered = TokenScopes::offeredTo($user);

        if ($this->option('scopes') !== null) {
            $picked = $this->option('scopes') === 'all'
                ? array_column($offered, 'key')
                : array_map('trim', explode(',', $this->option('scopes')));
        } else {
            $picked = multiselect(
                label: 'What this token may do',
                options: collect($offered)->mapWithKeys(fn (array $s) => [$s['key'] => "{$s['label']}: {$s['description']}"])->all(),
                default: array_column(array_filter($offered, fn (array $s) => $s['default']), 'key'),
            );
        }

        $abilities = TokenScopes::abilitiesFor($user, array_values($picked));

        if ($abilities === []) {
            $this->error('None of these scopes is allowed for '.$user->email.'. Offered: '.implode(', ', array_column($offered, 'key')).'.');

            return self::FAILURE;
        }

        $token = $user->createToken($name, ['mcp', ...$abilities], (int) $days === 0 ? null : now()->addDays((int) $days));
        $url = url('/mcp');

        $this->newLine();
        $this->line("Token for {$user->email} ({$name}), scopes: ".implode(', ', TokenScopes::of($token->accessToken)));
        $this->line('Expires: '.($token->accessToken->expires_at?->format('Y-m-d') ?? 'never'));
        $this->newLine();
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line("Endpoint: {$url}");
        $this->line("claude mcp add --transport http --scope user logger {$url} --header \"Authorization: Bearer {$token->plainTextToken}\"");
        $this->newLine();
        $this->warn('The token is shown only now. It cannot be read back later.');
        if (! $user->mcp_external) {
            $this->warn("{$user->email} lacks mcp.external: /mcp refuses this token until php artisan mcp:external {$user->email}.");
        }

        return self::SUCCESS;
    }
}
