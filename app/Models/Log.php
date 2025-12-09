<?php

namespace App\Models;

use App\Events\LogCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'level',
        'channel',
        'message',
        'context',
        'extra',
        'controller',
        'route_name',
        'method',
        'request_url',
        'user_id',
        'ip_address',
        'user_agent',
        'app_env',
        'app_debug',
        'referrer',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
        'app_debug' => 'boolean',
        'created_at' => 'datetime',
        'logged_at' => 'datetime',
    ];

    /**
     * The event map for the model.
     */
    protected $dispatchesEvents = [
        'created' => LogCreated::class,
    ];

    /**
     * Valid log levels (PSR-3 compliant).
     */
    public const LEVELS = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    ];

    /**
     * Log level severity (higher = more severe, PSR-3 compliant).
     */
    public const LEVEL_SEVERITY = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    ];

    /**
     * Get the project that owns the log.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if this log meets or exceeds the given severity level.
     */
    public function meetsThreshold(string $threshold): bool
    {
        $logSeverity = self::LEVEL_SEVERITY[$this->level] ?? 0;
        $thresholdSeverity = self::LEVEL_SEVERITY[$threshold] ?? 0;

        return $logSeverity >= $thresholdSeverity;
    }

    /**
     * Scope to filter by level.
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter by controller.
     */
    public function scopeController($query, string $controller)
    {
        return $query->where('controller', $controller);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to search message using full-text search when available.
     * Falls back to LIKE for SQLite or when full-text search is not available.
     */
    public function scopeSearchMessage($query, string $search)
    {
        $driver = $query->getConnection()->getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Use MySQL FULLTEXT search with MATCH AGAINST
            // Boolean mode allows partial matching and is more forgiving
            return $query->whereRaw(
                'MATCH(message) AGAINST(? IN BOOLEAN MODE)',
                [$this->prepareFullTextSearch($search)]
            );
        } elseif ($driver === 'pgsql') {
            // Use PostgreSQL full-text search
            return $query->whereRaw(
                "message_search @@ plainto_tsquery('english', ?)",
                [$search]
            );
        }
        
        // Fallback to LIKE for SQLite and other databases
        return $query->where('message', 'like', "%{$search}%");
    }

    /**
     * Prepare search string for MySQL FULLTEXT boolean mode.
     * Adds + prefix to require words and handles special characters.
     */
    protected function prepareFullTextSearch(string $search): string
    {
        // Split into words, filter empty, and add + prefix for required match
        $words = array_filter(explode(' ', trim($search)));
        
        return implode(' ', array_map(function ($word) {
            // Remove special boolean mode characters except asterisk for wildcards
            $word = preg_replace('/[+\-><()~*"@]/', '', $word);
            
            if (strlen($word) >= 3) {
                // Add wildcard for partial matching
                return '+' . $word . '*';
            }
            
            return $word;
        }, $words));
    }

    /**
     * Scope for simple LIKE search (useful when full-text is not desired).
     */
    public function scopeSearchMessageLike($query, string $search)
    {
        return $query->where('message', 'like', "%{$search}%");
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
