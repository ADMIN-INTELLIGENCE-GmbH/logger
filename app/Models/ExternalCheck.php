<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ExternalCheck extends Model
{
    use HasFactory;

    public const GROUPABLE_FIELDS = [
        'message',
        'controller',
        'route_name',
        'level',
    ];

    public const DEFAULT_GROUP_BY = [
        'message',
        'controller',
        'route_name',
        'level',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'enabled',
        'token_hash',
        'encrypted_token',
        'token_last_eight',
        'min_level',
        'time_window_minutes',
        'count_threshold',
        'group_by',
        'group_across_projects',
        'selector_tags',
        'included_project_ids',
        'excluded_project_ids',
        'memory_percent_gte',
        'disk_percent_gte',
        'token_generated_at',
    ];

    protected $hidden = [
        'token_hash',
        'encrypted_token',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'group_by' => 'array',
        'group_across_projects' => 'boolean',
        'selector_tags' => 'array',
        'included_project_ids' => 'array',
        'excluded_project_ids' => 'array',
        'memory_percent_gte' => 'decimal:2',
        'disk_percent_gte' => 'decimal:2',
        'token_generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExternalCheck $externalCheck) {
            if (empty($externalCheck->slug)) {
                $externalCheck->slug = static::generateUniqueSlug($externalCheck->name);
            }

            if (empty($externalCheck->group_by)) {
                $externalCheck->group_by = self::DEFAULT_GROUP_BY;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'external-check';
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = ($baseSlug !== '' ? $baseSlug : 'external-check').'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function normalizeTagNames(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => Str::lower($tag))
            ->values()
            ->all();
    }

    public function rotateToken(): string
    {
        $plainTextToken = Str::random(40);

        $this->fillTokenFromPlainText($plainTextToken);
        $this->save();

        return $plainTextToken;
    }

    public function fillTokenFromPlainText(string $plainTextToken): void
    {
        $this->forceFill([
            'token_hash' => hash('sha256', $plainTextToken),
            'encrypted_token' => Crypt::encryptString($plainTextToken),
            'token_last_eight' => substr($plainTextToken, -8),
            'token_generated_at' => now(),
        ]);
    }

    public function plainTextToken(): ?string
    {
        if (empty($this->encrypted_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_token);
        } catch (DecryptException) {
            return null;
        }
    }

    public function matchesToken(?string $plainTextToken): bool
    {
        if (empty($plainTextToken) || empty($this->token_hash)) {
            return false;
        }

        return hash_equals($this->token_hash, hash('sha256', $plainTextToken));
    }

    public function resolveProjects(): Collection
    {
        $user = $this->user;

        if (! $user) {
            return collect();
        }

        $selectorTags = collect($this->selector_tags ?? [])
            ->map(fn ($tag) => Str::lower(trim((string) $tag)))
            ->filter()
            ->values();

        $includedProjectIds = collect($this->included_project_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values();

        $excludedProjectIds = collect($this->excluded_project_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values();

        $visibleProjects = Project::query()
            ->visibleTo($user)
            ->with('tags')
            ->orderBy('name')
            ->get();

        return $visibleProjects
            ->filter(function (Project $project) use ($selectorTags, $includedProjectIds, $excludedProjectIds) {
                if ($excludedProjectIds->contains((string) $project->id)) {
                    return false;
                }

                if ($selectorTags->isEmpty() && $includedProjectIds->isEmpty()) {
                    return true;
                }

                if ($includedProjectIds->contains((string) $project->id)) {
                    return true;
                }

                $projectTagNames = $project->tags
                    ->pluck('name')
                    ->map(fn ($tag) => Str::lower($tag));

                return $projectTagNames->intersect($selectorTags)->isNotEmpty();
            })
            ->values();
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
