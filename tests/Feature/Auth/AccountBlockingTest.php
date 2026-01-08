<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for account blocking functionality.
 * Verifies that inactive users cannot login via email or OAuth.
 */
class AccountBlockingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an inactive user cannot login with email/password.
     */
    public function test_inactive_user_cannot_login_with_email(): void
    {
        // Create an inactive user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'active' => false,
        ]);

        // Attempt to login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should redirect back with error
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        
        // Verify error message contains inactive account notice
        $errors = session('errors');
        $this->assertNotNull($errors);
        $errorMessage = $errors->first('email');
        $this->assertTrue(
            str_contains($errorMessage, 'disabled') || str_contains($errorMessage, 'désactivé'),
            "Expected error message to contain 'disabled' or 'désactivé', got: {$errorMessage}"
        );
        
        // Verify user is not authenticated
        $this->assertGuest();
    }

    /**
     * Test that an active user can login with email/password.
     */
    public function test_active_user_can_login_with_email(): void
    {
        // Create an active user
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => bcrypt('password'),
            'active' => true,
        ]);

        // Attempt to login
        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'password',
        ]);

        // Should redirect to home
        $response->assertRedirect('/');
        
        // Verify user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that toggling user active status works.
     */
    public function test_admin_can_toggle_user_active_status(): void
    {
        // Create admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create regular user
        $user = User::factory()->create(['active' => true]);

        // Login as admin
        $this->actingAs($admin);

        // Toggle user status to inactive
        $response = $this->post(route('admin.users.toggle', $user));
        
        $response->assertRedirect();
        
        // Verify user is now inactive (0 or false)
        $this->assertEquals(0, $user->fresh()->active);

        // Toggle back to active
        $response = $this->post(route('admin.users.toggle', $user));
        
        // Verify user is now active (1 or true)
        $this->assertEquals(1, $user->fresh()->active);
    }

    /**
     * Test that inactive users cannot login even with correct credentials.
     */
    public function test_inactive_user_is_logged_out_immediately(): void
    {
        // Create user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'active' => true,
        ]);

        // Deactivate user
        $user->active = false;
        $user->save();

        // Attempt to login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should not be authenticated
        $this->assertGuest();
    }
}
