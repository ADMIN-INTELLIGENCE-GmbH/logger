<?php

namespace App\Mcp;

use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * What an MCP token may do, picked per token at creation (Profile > API
 * Tokens). Abilities: "mcp" marks the token, one "mcp:<scope>" per ticked
 * scope. The choice is capped at the owner's rights when the token is made;
 * the tools still check project permissions on every call.
 *
 * Tokens from before per-token scopes carry only "mcp" and keep every tool,
 * as they always did, until they are revoked.
 */
final class TokenScopes
{
    /**
     * scope => tools it opens.
     *
     * @var array<string, list<string>>
     */
    public const TOOLS = [
        'read' => ['list_projects', 'project_overview', 'new_log_groups', 'search_logs', 'get_log'],
        'resolve_log_group' => ['resolve_log_group'],
        'get_ingest_config' => ['get_ingest_config'],
        'create_project' => ['create_project'],
    ];

    /**
     * Scopes this user may put on a token, with what the form shows.
     *
     * @return list<array{key: string, label: string, description: string, default: bool}>
     */
    public static function offeredTo(User $user): array
    {
        $canEdit = $user->isAdmin() || Project::editableBy($user)->exists();

        return array_values(array_filter([
            ['key' => 'read', 'label' => 'Read logs', 'description' => 'list_projects, project_overview, new_log_groups, search_logs, get_log', 'default' => true],
            $canEdit ? ['key' => 'resolve_log_group', 'label' => 'Resolve log groups', 'description' => 'resolve_log_group: mark a group as fixed, or reopen it', 'default' => false] : null,
            $canEdit ? ['key' => 'get_ingest_config', 'label' => 'Read project keys', 'description' => 'get_ingest_config: returns the secret ingest key', 'default' => false] : null,
            $user->can('create', Project::class) ? ['key' => 'create_project', 'label' => 'Create projects', 'description' => 'create_project (admins)', 'default' => false] : null,
        ]));
    }

    /**
     * Abilities for the ticked scopes; anything not offered to the user is dropped.
     *
     * @param  list<string>  $scopes
     * @return list<string>
     */
    public static function abilitiesFor(User $user, array $scopes): array
    {
        $offered = array_column(self::offeredTo($user), 'key');

        return array_values(array_map(
            static fn (string $scope): string => 'mcp:'.$scope,
            array_filter($offered, static fn (string $key): bool => in_array($key, $scopes, true)),
        ));
    }

    /**
     * Ticked scopes of a token; null for a token from before scopes (all tools).
     *
     * @return list<string>|null
     */
    public static function of(HasAbilities $token): ?array
    {
        $scopes = [];

        foreach ((array) ($token->abilities ?? []) as $ability) {
            if (is_string($ability) && str_starts_with($ability, 'mcp:')) {
                $scopes[] = substr($ability, 4);
            }
        }

        return $scopes === [] ? null : $scopes;
    }

    public static function allows(HasAbilities $token, string $tool): bool
    {
        $scopes = self::of($token);

        if ($scopes === null) {
            return true;
        }

        foreach ($scopes as $scope) {
            if (in_array($tool, self::TOOLS[$scope] ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
