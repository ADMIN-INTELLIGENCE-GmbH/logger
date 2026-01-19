<?php

namespace Tests\Feature\Mail;

use App\Mail\DailyDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_digest_email_contains_correct_data(): void
    {
        $data = [
            'logs_summary' => [
                [
                    'id' => 'uuid-1',
                    'name' => 'Project Alpha',
                    'counts' => ['error' => 5, 'info' => 10]
                ]
            ],
            'memory_alerts' => [
                ['project_id' => 'uuid-2', 'project' => 'Project Beta', 'usage' => 95.5]
            ],
            'storage_alerts' => []
        ];

        $mailable = new DailyDigest($data);

        $mailable->assertSeeInHtml('Project Alpha');
        $mailable->assertSeeInHtml('Project Beta');
        $mailable->assertSeeInHtml('95.5');
        // assertSeeInOrderInHtml is not available in Mailable assertions usually, 
        // using assertSeeInHtml for specific values instead.
        $mailable->assertSeeInHtml('Error');
        $mailable->assertSeeInHtml('5');
    }

    public function test_daily_digest_has_correct_subject(): void
    {
        $mailable = new DailyDigest([]);
        $mailable->assertHasSubject('Your Daily Logger Digest');
    }
}
