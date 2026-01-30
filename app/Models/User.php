<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available user roles.
     */
    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'timezone',
        'dashboard_preferences',
        'daily_digest_enabled',
        'daily_digest_at',
        'daily_digest_settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_preferences' => 'array',
            'daily_digest_enabled' => 'boolean',
            'daily_digest_settings' => 'array',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if the user is a regular user.
     */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Get the projects assigned to the user.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Check if the user can view the given project.
     */
    public function canViewProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->projects()
            ->where('projects.id', $project->id)
            ->exists();
    }

    /**
     * Check if the user can edit the given project.
     */
    public function canEditProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->projects()
            ->where('projects.id', $project->id)
            ->wherePivot('permission', Project::PERMISSION_EDIT)
            ->exists();
    }
}
