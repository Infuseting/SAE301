<?php

namespace Tests\Feature\Club;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions if they don't exist (using firstOrCreate avoids collisions if seeder runs)
        Permission::firstOrCreate(['name' => 'create-club', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'accept-club', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'club-manager', 'guard_name' => 'web']);
    }

    public function test_user_can_create_club_and_it_is_pending()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create-club');

        $response = $this->actingAs($user)->post(route('clubs.store'), [
            'club_name' => 'Test Club',
            'club_street' => '123 Test St',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFCO-TEST',
            'description' => 'A test club'
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('clubs', [
            'club_name' => 'Test Club',
            'is_approved' => 0, // False
            'created_by' => $user->id
        ]);
    }

    public function test_creator_cannot_see_pending_club()
    {
        $user = User::factory()->create();
        $club = Club::factory()->create([
            'created_by' => $user->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($user)->get(route('clubs.index'));

        $response->assertStatus(200);
        $response->assertDontSee($club->club_name);
    }


    public function test_admin_can_approve_club()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['accept-club', 'access-admin']);

        $creator = User::factory()->create();
        $club = Club::factory()->create([
            'created_by' => $creator->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($admin)->post(route('admin.clubs.approve', $club->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clubs', [
            'club_id' => $club->club_id,
            'is_approved' => 1 // True
        ]);

        // Verify creator is now a manager using sync check
        $this->assertTrue($club->refresh()->hasManager($creator));
    }

    public function test_approval_handles_duplicate_entry_gracefully()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['accept-club', 'access-admin']);

        $creator = User::factory()->create();
        $club = Club::factory()->create([
            'created_by' => $creator->id,
            'is_approved' => false
        ]);

        // Simulate pre-existing membership to trigger potential duplicate entry error
        $club->members()->attach($creator->id, ['role' => 'manager', 'status' => 'pending']);

        // Attempt approval
        $response = $this->actingAs($admin)->post(route('admin.clubs.approve', $club->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Should be approved
        $this->assertTrue($club->refresh()->is_approved);
    }

    public function test_unapproved_club_is_404_for_stranger()
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();

        $club = Club::factory()->create([
            'created_by' => $creator->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($stranger)->get(route('clubs.show', $club->club_id));

        $response->assertStatus(404);
    }

    public function test_unapproved_club_is_visible_for_creator()
    {
        $creator = User::factory()->create();

        $club = Club::factory()->create([
            'created_by' => $creator->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($creator)->get(route('clubs.show', $club->club_id));

        $response->assertStatus(200);
    }

    public function test_api_club_show_returns_members_for_approved_member(): void
    {
        $manager = User::factory()->create();
        $member = User::factory()->create();
        $club = Club::factory()->create(['is_approved' => true, 'created_by' => $manager->id]);

        $club->allMembers()->attach($manager->id, ['role' => 'manager', 'status' => 'approved']);
        $club->allMembers()->attach($member->id, ['role' => 'member', 'status' => 'approved']);

        $response = $this->actingAs($member)->getJson('/api/clubs/' . $club->club_id);

        $response->assertOk();
        $response->assertJsonPath('data.isMember', true);
        $response->assertJsonCount(2, 'data.members');
        $response->assertJsonMissingPath('data.pending_members');
    }

    public function test_api_club_show_returns_pending_members_for_manager_and_admin(): void
    {
        $manager = User::factory()->create();
        $pendingMember = User::factory()->create();
        $club = Club::factory()->create(['is_approved' => true, 'created_by' => $manager->id]);

        $club->allMembers()->attach($manager->id, ['role' => 'manager', 'status' => 'approved']);
        $club->allMembers()->attach($pendingMember->id, ['role' => 'member', 'status' => 'pending']);

        $managerResponse = $this->actingAs($manager)->getJson('/api/clubs/' . $club->club_id);

        $managerResponse->assertOk();
        $managerResponse->assertJsonPath('data.isManager', true);
        $managerResponse->assertJsonCount(1, 'data.pending_members');

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $adminResponse = $this->actingAs($admin)->getJson('/api/clubs/' . $club->club_id);

        $adminResponse->assertOk();
        $adminResponse->assertJsonPath('data.isManager', true);
        $adminResponse->assertJsonCount(1, 'data.pending_members');
        $adminResponse->assertJsonMissingPath('data.members');
    }
}
