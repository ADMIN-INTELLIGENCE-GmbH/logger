<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The Passport guard on /mcp needs its key pair even to reject a request.
        if (! is_file(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys');
        }
    }
}
