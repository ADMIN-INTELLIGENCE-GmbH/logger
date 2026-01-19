<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\User;
use App\Services\DailyDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-test-digest {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test daily digest to a specific user immediately';

    /**
     * Execute the console command.
     */
    public function handle(DailyDigestService $digestService)
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found: {$email}");

            return;
        }

        $this->info("Gathering data for {$user->email}...");

        // Gather data without pre-fetching optimization, relying on the service to do individual queries
        // (Is fine for a test command)
        $data = $digestService->gatherData($user);

        $this->info('Sending email...');

        try {
            Mail::to($user)->send(new DailyDigest($data));
            $this->info('Test digest sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send email: '.$e->getMessage());
        }
    }
}
