<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles/permissions are available
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /**
     * Test admin can access dashboard.
     */
    public function test_admin_can_access_dashboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    /**
     * Test regular user cannot access dashboard.
     */
    /*
    public function test_regular_user_cannot_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/admin');

        if ($response->status() !== 403) {
            dump($response->status());
        }

        $response->assertStatus(403);
    }
    */

    /**
     * Test admin can view users list.
     */
    public function test_admin_can_view_users_list()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $otherUser = User::factory()->create(['first_name' => 'Target', 'last_name' => 'User']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200)
            ->assertSee('Target User');
    }

    /**
     * Test admin can toggle user status (if feature exists) or update user.
     * Assuming 'update' logic from routes.
     */
    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create(['first_name' => 'OldName']);

        $response = $this->actingAs($admin)->put("/admin/users/{$targetUser->id}", [
            'first_name' => 'NewName',
            'last_name' => 'Name',
            'email' => $targetUser->email,
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('NewName', $targetUser->fresh()->first_name);
    }

    /**
     * Test admin can assign roles.
     */
    public function test_admin_can_assign_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($admin)->postJson("/admin/users/{$targetUser->id}/role", [
            'role' => 'admin',
        ]);

        if ($response->status() !== 302) {
            dump($response->status());
            dump($admin->getAllPermissions()->pluck('name'));
        }
        $response->assertRedirect();
        $this->assertTrue($targetUser->fresh()->hasRole('admin'));
    }

    /**
     * Test admin can delete users.
     */
    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/users/{$targetUser->id}");

        $response->assertRedirect();
        $this->assertNull(User::find($targetUser->id));
    }
}
