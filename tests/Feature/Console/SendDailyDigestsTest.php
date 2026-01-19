<?php

namespace Tests\Feature\Console;

use App\Mail\DailyDigest;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDailyDigestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_digests_to_users_at_their_scheduled_time(): void
    {
        Mail::fake();
        Project::factory()->create(); // Ensure we have logs/projects to report on

        // User in UTC, scheduled for 09:00
        $utcUser = User::factory()->create([
            'daily_digest_enabled' => true,
            'timezone' => 'UTC',
            'daily_digest_at' => '09:00:00',
        ]);

        // User in Tokyo (UTC+9), scheduled for 09:00 (which is 00:00 UTC)
        $tokyoUser = User::factory()->create([
            'daily_digest_enabled' => true,
            'timezone' => 'Asia/Tokyo',
            'daily_digest_at' => '09:00:00', // 9 AM in Tokyo
        ]);

        // 1. Test at 09:00 UTC
        // Should send to UTC user (it's 9 AM for them)
        // Should NOT send to Tokyo user (it's 6 PM for them)
        Carbon::setTestNow(Carbon::createFromTime(9, 0, 0, 'UTC'));

        // Debug
        // dump(User::all()->toArray());

        Artisan::call('app:send-daily-digests');

        dump('UTC User Email:', $utcUser->email);
        $queued = Mail::queued(DailyDigest::class);
        $sent = Mail::sent(DailyDigest::class);
        dump('Queued count: '.$queued->count());
        dump('Sent count: '.$sent->count());
        foreach ($queued as $mail) {
            dump('Queued Mail To: ', $mail->to);
        }

        Mail::assertSent(DailyDigest::class, function ($mail) use ($utcUser) {
            return $mail->hasTo($utcUser->email);
        });

        Mail::assertNotQueued(DailyDigest::class, function ($mail) use ($tokyoUser) {
            return $mail->hasTo($tokyoUser->email);
        });
    }

    public function test_does_not_send_digests_if_disabled(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'daily_digest_enabled' => false,
            'timezone' => 'UTC',
            'daily_digest_at' => '09:00:00',
        ]);

        Carbon::setTestNow(Carbon::createFromTime(9, 0, 0, 'UTC'));

        Artisan::call('app:send-daily-digests');

        Mail::assertNothingSent();
    }

    public function test_handles_invalid_timezones_gracefully(): void
    {
        Mail::fake();
        Project::factory()->create();

        $user = User::factory()->create([
            'daily_digest_enabled' => true,
            'timezone' => 'Invalid/Timezone',
            'daily_digest_at' => '09:00:00',
        ]);

        // Should default to UTC
        Carbon::setTestNow(Carbon::createFromTime(9, 0, 0, 'UTC'));

        Artisan::call('app:send-daily-digests');

        Mail::assertSent(DailyDigest::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
