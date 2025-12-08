<?php

namespace App\Events;

use App\Models\Log;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The log instance.
     */
    public Log $log;

    /**
     * Create a new event instance.
     */
    public function __construct(Log $log)
    {
        $this->log = $log;
    }
}
