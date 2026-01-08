<?php

namespace Tests\Feature\Club;

use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test suite for Club membership management
 * 
 * Tests cover:
 * - Users can request to join a club
 * - Club managers can approve join requests
 * - Club managers can reject join requests
 * - Non-managers cannot approve/reject requests
 * - Edge cases and validation
 */
class ClubMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $clubManager;
    private User $regularUser;
    private User $anotherUser;
    private Club $club;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie Permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create necessary permissions and roles
        Permission::firstOrCreate(['name' => 'create-club', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'accept-club', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'club-manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'adherent', 'guard_name' => 'web']);

        // Create club manager with adherent credentials
        $managerDoc = MedicalDoc::factory()->create();
        $managerMember = Member::factory()->create();
        $this->clubManager = User::factory()->create([
            'adh_id' => $managerMember->adh_id,
            'doc_id' => $managerDoc->doc_id,
        ]);
        $this->clubManager->givePermissionTo('create-club');

        // Create an approved club with the manager
        $this->club = Club::factory()->create([
            'created_by' => $this->clubManager->id,
            'is_approved' => true,
        ]);

        // Add the club manager as a manager
        $this->club->allMembers()->attach($this->clubManager->id, [
            'role' => 'manager',
            'status' => 'approved',
        ]);

        // Create a regular user who wants to join
        $regularDoc = MedicalDoc::factory()->create();
        $regularMember = Member::factory()->create();
        $this->regularUser = User::factory()->create([
            'adh_id' => $regularMember->adh_id,
            'doc_id' => $regularDoc->doc_id,
        ]);
        $this->regularUser->assignRole('adherent');

        // Create another user (not a manager)
        $anotherDoc = MedicalDoc::factory()->create();
        $anotherMember = Member::factory()->create();
        $this->anotherUser = User::factory()->create([
            'adh_id' => $anotherMember->adh_id,
            'doc_id' => $anotherDoc->doc_id,
        ]);
        $this->anotherUser->assignRole('adherent');
    }

    // ===========================================
    // JOIN REQUEST TESTS
    // ===========================================

    /**
     * Test that a user can request to join an approved club
     */
    public function test_user_can_request_to_join_club(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('clubs.join', $this->club->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the join request was created with pending status
        $this->assertDatabaseHas('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test that a user cannot request to join an unapproved club
     */
    public function test_user_cannot_request_to_join_unapproved_club(): void
    {
        $unapprovedClub = Club::factory()->create([
            'created_by' => $this->clubManager->id,
            'is_approved' => false,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('clubs.join', $unapprovedClub->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify no join request was created
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $unapprovedClub->club_id,
            'user_id' => $this->regularUser->id,
        ]);
    }

    /**
     * Test that a user cannot send duplicate join requests
     */
    public function test_user_cannot_send_duplicate_join_request(): void
    {
        // First request
        $this->actingAs($this->regularUser)
            ->post(route('clubs.join', $this->club->club_id));

        // Second request should fail
        $response = $this->actingAs($this->regularUser)
            ->post(route('clubs.join', $this->club->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify only one request exists
        $this->assertEquals(1, $this->club->pendingRequests()
            ->where('user_id', $this->regularUser->id)
            ->count());
    }

    // ===========================================
    // APPROVE JOIN REQUEST TESTS
    // ===========================================

    /**
     * Test that a club manager can approve a join request
     */
    public function test_club_manager_can_approve_join_request(): void
    {
        // Create a pending join request
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        // Manager approves the request
        $response = $this->actingAs($this->clubManager)
            ->post(route('clubs.members.approve', [
                'club' => $this->club->club_id,
                'user' => $this->regularUser->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the status was updated to approved
        $this->assertDatabaseHas('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test that a non-manager cannot approve a join request
     */
    public function test_non_manager_cannot_approve_join_request(): void
    {
        // Create a pending join request
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        // Non-manager tries to approve
        $response = $this->actingAs($this->anotherUser)
            ->post(route('clubs.members.approve', [
                'club' => $this->club->club_id,
                'user' => $this->regularUser->id,
            ]));

        $response->assertStatus(403);

        // Verify the status is still pending
        $this->assertDatabaseHas('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
            'status' => 'pending',
        ]);
    }

    // ===========================================
    // REJECT JOIN REQUEST TESTS
    // ===========================================

    /**
     * Test that a club manager can reject a join request
     */
    public function test_club_manager_can_reject_join_request(): void
    {
        // Create a pending join request
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        // Manager rejects the request
        $response = $this->actingAs($this->clubManager)
            ->post(route('clubs.members.reject', [
                'club' => $this->club->club_id,
                'user' => $this->regularUser->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the request was removed
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
        ]);
    }

    /**
     * Test that a non-manager cannot reject a join request
     */
    public function test_non_manager_cannot_reject_join_request(): void
    {
        // Create a pending join request
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        // Non-manager tries to reject
        $response = $this->actingAs($this->anotherUser)
            ->post(route('clubs.members.reject', [
                'club' => $this->club->club_id,
                'user' => $this->regularUser->id,
            ]));

        $response->assertStatus(403);

        // Verify the request still exists
        $this->assertDatabaseHas('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
            'status' => 'pending',
        ]);
    }

    // ===========================================
    // ROUTE MODEL BINDING TESTS
    // ===========================================

    /**
     * Test that route model binding works correctly with club_id
     * This test verifies the fix for the getRouteKeyName issue
     */
    public function test_route_model_binding_uses_club_id(): void
    {
        // Create a pending join request
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        // Use the club_id in the URL (not the auto-increment id)
        $clubId = $this->club->club_id;

        // This should work with the correct route key name
        $response = $this->actingAs($this->clubManager)
            ->post("/clubs/{$clubId}/members/{$this->regularUser->id}/approve");

        // Should not return 404 (club not found)
        $this->assertNotEquals(404, $response->status());

        // Should successfully approve (302 redirect with success message)
        $response->assertRedirect();
    }

    // ===========================================
    // LEAVE CLUB TESTS
    // ===========================================

    /**
     * Test that an approved member can leave a club
     */
    public function test_approved_member_can_leave_club(): void
    {
        // Add user as approved member
        $this->club->allMembers()->attach($this->regularUser->id, [
            'role' => 'member',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('clubs.leave', $this->club->club_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the user was removed from the club
        $this->assertDatabaseMissing('club_user', [
            'club_id' => $this->club->club_id,
            'user_id' => $this->regularUser->id,
        ]);
    }
}
