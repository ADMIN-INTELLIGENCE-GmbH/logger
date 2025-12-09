<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'log_id',
        'url',
        'event_type',
        'payload',
        'status_code',
        'response_body',
        'error_message',
        'success',
        'attempt',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'attempt' => 'integer',
        'status_code' => 'integer',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the project that owns this delivery.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the log that triggered this delivery.
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class);
    }

    /**
     * Scope to get recent deliveries.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get failed deliveries.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope to get successful deliveries.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }
}
