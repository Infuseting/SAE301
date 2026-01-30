<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ManagedClubsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the manager role exists in the database
        Role::firstOrCreate(['name' => 'club-manager', 'guard_name' => 'web']);
    }

    public function test_unauthenticated_user_cannot_access_managed_clubs()
    {
        $response = $this->getJson('/api/me/managed-clubs');

        $response->assertStatus(401);
    }

    public function test_user_can_see_clubs_they_created()
    {
        $user = User::factory()->create();
        $myClub = Club::factory()->create(['created_by' => $user->id, 'club_name' => 'My Owned Club']);
        Club::factory()->create(['club_name' => 'Other Club']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me/managed-clubs');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['club_name' => 'My Owned Club'])
            ->assertJsonMissing(['club_name' => 'Other Club']);
    }

    public function test_user_can_see_clubs_they_manage()
    {
        $user = User::factory()->create();
        $managedClub = Club::factory()->create(['club_name' => 'Managed Club']);

        // Attach user as manager
        $managedClub->allMembers()->attach($user->id, [
            'role' => 'manager',
            'status' => 'approved'
        ]);

        Club::factory()->create(['club_name' => 'Just a Member Club']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me/managed-clubs');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['club_name' => 'Managed Club']);
    }

    public function test_admin_can_see_all_clubs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Club::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/me/managed-clubs');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }
}
