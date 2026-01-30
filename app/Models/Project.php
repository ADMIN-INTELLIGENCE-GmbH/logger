<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, HasUuids;

    /**
     * Supported webhook formats.
     */
    public const WEBHOOK_FORMATS = [
        'slack' => 'Slack',
        'mattermost' => 'Mattermost',
        'discord' => 'Discord',
        'teams' => 'Microsoft Teams',
        'generic' => 'Generic JSON',
    ];

    /**
     * Supported project permissions.
     */
    public const PERMISSION_VIEW = 'view';

    public const PERMISSION_EDIT = 'edit';

    public const PERMISSIONS = [
        self::PERMISSION_VIEW,
        self::PERMISSION_EDIT,
    ];

    protected $fillable = [
        'name',
        'magic_key',
        'allowed_domains',
        'retention_days',
        'webhook_url',
        'webhook_enabled',
        'webhook_threshold',
        'webhook_format',
        'webhook_secret',
        'is_active',
        'server_stats',
        'last_server_stats_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'webhook_enabled' => 'boolean',
        'retention_days' => 'integer',
        'server_stats' => 'array',
        'last_server_stats_at' => 'datetime',
        'allowed_domains' => 'array',
    ];

    protected $hidden = [
        'magic_key',
        'webhook_secret',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $project) {
            if (empty($project->magic_key)) {
                $project->magic_key = static::generateMagicKey();
            }
        });
    }

    /**
     * Generate a unique 64 character magic key.
     */
    public static function generateMagicKey(): string
    {
        return Str::random(64);
    }

    /**
     * Regenerate the magic key.
     */
    public function regenerateMagicKey(): self
    {
        $this->magic_key = static::generateMagicKey();
        $this->save();

        return $this;
    }

    /**
     * Find a project by its magic key.
     */
    public static function findByMagicKey(string $key): ?self
    {
        return static::where('magic_key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if the given origin is allowed.
     */
    public function isOriginAllowed(?string $origin): bool
    {
        // If no domains are configured, allow all
        if (empty($this->allowed_domains)) {
            return true;
        }

        // If origin is missing but domains are restricted, block it
        if (empty($origin)) {
            return false;
        }

        // Normalize origin (remove protocol)
        $host = parse_url($origin, PHP_URL_HOST) ?? $origin;

        foreach ($this->allowed_domains as $domain) {
            // Exact match
            if ($domain === $host) {
                return true;
            }

            // Wildcard match (e.g. *.example.com)
            if (str_starts_with($domain, '*.')) {
                $suffix = substr($domain, 2);
                if (str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all logs for this project.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get all webhook deliveries for this project.
     */
    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Get all tags for this project.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get all users assigned to this project.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Scope projects visible to the given user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $builder) use ($user) {
            $builder->where('users.id', $user->id);
        });
    }

    /**
     * Scope projects editable by the given user.
     */
    public function scopeEditableBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $builder) use ($user) {
            $builder->where('users.id', $user->id)
                ->where('project_user.permission', self::PERMISSION_EDIT);
        });
    }

    /**
     * Check if the project has infinite retention.
     */
    public function hasInfiniteRetention(): bool
    {
        return $this->retention_days === -1;
    }

    /**
     * Check if the project has a webhook configured and enabled.
     */
    public function hasWebhook(): bool
    {
        return ! empty($this->webhook_url) && $this->webhook_enabled;
    }

    /**
     * Check if the project has a webhook URL (regardless of enabled state).
     */
    public function hasWebhookUrl(): bool
    {
        return ! empty($this->webhook_url);
    }

    /**
     * Generate a new webhook secret.
     */
    public function regenerateWebhookSecret(): self
    {
        $this->webhook_secret = Str::random(64);
        $this->save();

        return $this;
    }
}
