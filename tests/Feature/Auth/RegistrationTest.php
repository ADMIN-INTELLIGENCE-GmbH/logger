<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->get('/register');

        $response->assertRedirect(route('login'));
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        config(['app.registration_enabled' => true]);

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_when_enabled(): void
    {
        config(['app.registration_enabled' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('projects.index', absolute: false));
    }

    public function test_registration_is_blocked_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(403);
        $this->assertGuest();
    }
}
