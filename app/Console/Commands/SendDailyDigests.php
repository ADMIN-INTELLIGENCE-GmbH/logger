<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\Log;
use App\Models\Project;
use App\Models\User;
use App\Services\DailyDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-digests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily email digests to users based on their preferences';

    /**
     * Execute the console command.
     */
    public function handle(DailyDigestService $digestService)
    {
        // 1. Pre-fetch all active project data (Performance Optimization)
        $projects = Project::where('is_active', true)->get();
        if ($projects->isEmpty()) {
            return;
        }

        // 2. Pre-calculate Log Counts for ALL projects
        $logStats = Log::where('created_at', '>=', now()->subDay())
            ->whereIn('project_id', $projects->pluck('id'))
            ->selectRaw('project_id, level, count(*) as count')
            ->groupBy('project_id', 'level')
            ->get()
            ->groupBy('project_id');

        // 3. Find which users need a digest right now based on their Timezone
        // Get all distinct timezones from users who want digests
        $timezones = User::where('daily_digest_enabled', true)
            ->distinct()
            ->pluck('timezone');

        dump('Processing Timezones:', $timezones->toArray());

        foreach ($timezones as $timezone) {
            // Determine the current local time in that timezone
            // Default to UTC if timezone is invalid or missing
            try {
                $localTime = now($timezone)->format('H:i:00');
            } catch (\Exception $e) {
                dump("Invalid timezone encountered: $timezone");
                $localTime = now('UTC')->format('H:i:00');
            }

            dump("Timezone: {$timezone}, Local Time: {$localTime}");

            // Find users in this timezone who want a digest at this specific minute
            $users = User::where('daily_digest_enabled', true)
                ->where('timezone', $timezone)
                ->where('daily_digest_at', $localTime)
                ->get();

            dump("Found {$users->count()} users in {$timezone} scheduled for {$localTime}");
            if ($users->isNotEmpty()) {
                dump('User IDs: '.$users->pluck('id')->join(', '));
            }

            foreach ($users as $user) {
                dump('Sending to (Wait): '.$user->email);
                try {
                    $data = $digestService->gatherData($user, $projects, $logStats);
                    Mail::to($user)->send(new DailyDigest($data));
                    dump('Sent to: '.$user->email);
                } catch (\Exception $e) {
                    $this->error("Failed to send digest to {$user->email}: ".$e->getMessage());
                }
            }
        }
    }
}
