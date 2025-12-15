<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Tag extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Get all projects that have this tag.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Check if this tag is still being used by any project.
     */
    public function isInUse(): bool
    {
        return DB::table('project_tag')
            ->where('tag_id', $this->id)
            ->exists();
    }
}
