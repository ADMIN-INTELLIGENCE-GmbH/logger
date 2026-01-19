<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule log pruning daily at midnight
Schedule::command('app:prune-logs')->daily()->at('00:00');

// Process queued jobs every minute
Schedule::command('queue:work --stop-when-empty')->everyMinute();

// Send daily digests
Schedule::command('app:send-daily-digests')->everyMinute();
