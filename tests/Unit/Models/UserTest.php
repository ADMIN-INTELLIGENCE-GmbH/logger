<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_constants_are_defined(): void
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('user', User::ROLE_USER);
    }

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_user_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_is_user_returns_true_for_user_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertTrue($user->isUser());
    }

    public function test_is_user_returns_false_for_admin_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertFalse($user->isUser());
    }

    public function test_password_is_hidden_in_array(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_remember_token_is_hidden_in_array(): void
    {
        $user = User::factory()->create(['remember_token' => 'token123']);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plaintext123']);

        // Password should be hashed, not stored as plaintext
        $this->assertNotEquals('plaintext123', $user->password);
        $this->assertTrue(password_verify('plaintext123', $user->password));
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
}
