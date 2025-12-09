<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected $fillable = [
        'name',
        'magic_key',
        'retention_days',
        'webhook_url',
        'webhook_enabled',
        'webhook_threshold',
        'webhook_format',
        'webhook_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'webhook_enabled' => 'boolean',
        'retention_days' => 'integer',
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
