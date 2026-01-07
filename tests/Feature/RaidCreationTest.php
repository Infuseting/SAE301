<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test suite for Raid creation functionality
 * 
 * Tests cover:
 * - Access control (only club leaders can create raids)
 * - Validation rules (dates, required fields, relationships)
 * - Auto-assignment of club from authenticated user
 * - Automatic creation of registration periods
 * - Member verification within club
 */
class RaidCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $clubLeader;
    private User $clubMember;
    private User $nonClubUser;
    private int $clubId;
    private Member $leaderMember;
    private Member $regularMember;

    /**
     * Setup test environment before each test
     * Creates club, users, and members needed for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create medical documents
        $leaderDoc = MedicalDoc::factory()->create();
        $memberDoc = MedicalDoc::factory()->create();
        $nonClubDoc = MedicalDoc::factory()->create();

        // Create members
        $this->leaderMember = Member::factory()->create();
        $this->regularMember = Member::factory()->create();
        $nonClubMember = Member::factory()->create();

        // Create club leader user
        $this->clubLeader = User::factory()->create([
            'doc_id' => $leaderDoc->doc_id,
            'adh_id' => $this->leaderMember->adh_id,
        ]);

        // Create regular club member user
        $this->clubMember = User::factory()->create([
            'doc_id' => $memberDoc->doc_id,
            'adh_id' => $this->regularMember->adh_id,
        ]);

        // Create non-club user
        $this->nonClubUser = User::factory()->create([
            'doc_id' => $nonClubDoc->doc_id,
            'adh_id' => $nonClubMember->adh_id,
        ]);

        // Create club with leader using DB::table
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFSO999',
            'description' => 'Test Club Description',
            'is_approved' => true,
            'created_by' => $this->clubLeader->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add members to club via club_user pivot table
        DB::table('club_user')->insert([
            [
                'club_id' => $this->clubId,
                'user_id' => $this->clubLeader->id,
                'role' => 'manager',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'club_id' => $this->clubId,
                'user_id' => $this->clubMember->id,
                'role' => 'member',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Test that a club leader can access the raid creation form
     */
    public function test_club_leader_can_access_raid_creation_form(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.create'));

        $response->assertOk(); // ou ->assertSuccessful();

        $response->assertInertia(fn ($page) => $page
            ->component('Raid/Create')
            ->has('userClub')
            ->has('clubMembers')
        );
    }

    /**
     * Test that a non-club leader cannot access the raid creation form
     */
    public function test_non_club_leader_cannot_access_raid_creation_form(): void
    {
        $response = $this->actingAs($this->clubMember)
            ->get(route('raids.create'));

        $response->assertStatus(403);
    }

    /**
     * Test that a user without a club cannot access the raid creation form
     */
    public function test_user_without_club_cannot_access_raid_creation_form(): void
    {
        $response = $this->actingAs($this->nonClubUser)
            ->get(route('raids.create'));

        $response->assertStatus(403);
    }

    /**
     * Test that a club leader can successfully create a raid
     */
    public function test_club_leader_can_create_raid(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid 2026',
            'raid_description' => 'A test raid description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_site_url' => 'https://testraid.com',
            'raid_street' => '123 Raid Street',
            'raid_city' => 'Raid City',
            'raid_postal_code' => '54321',
            'raid_number' => 2026001,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertRedirect(route('raids.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('raids', [
            'raid_name' => 'Test Raid 2026',
            'clu_id' => $this->clubId,
            'adh_id' => $this->leaderMember->adh_id,
        ]);

        // Verify registration period was created
        $raid = Raid::where('raid_name', 'Test Raid 2026')->first();
        $this->assertNotNull($raid->ins_id);
        $this->assertDatabaseHas('registration_period', [
            'ins_id' => $raid->ins_id,
            'ins_start_date' => '2026-03-01 00:00:00',
            'ins_end_date' => '2026-05-31 00:00:00',
        ]);
    }

    /**
     * Test that raid creation requires all mandatory fields
     */
    public function test_raid_creation_requires_all_fields(): void
    {
        $requiredFields = [
            'raid_name',
            // 'raid_description', // Nullable
            'adh_id',
            'ins_start_date',
            'ins_end_date',
            'raid_date_start',
            'raid_date_end',
            'raid_contact',
            'raid_street',
            'raid_city',
            'raid_postal_code',
            'raid_number',
        ];

        foreach ($requiredFields as $field) {
            $raidData = [
                'raid_name' => 'Test Raid',
                'raid_description' => 'Description',
                'adh_id' => $this->leaderMember->adh_id,
                'ins_start_date' => '2026-03-01',
                'ins_end_date' => '2026-05-31',
                'raid_date_start' => '2026-06-01',
                'raid_date_end' => '2026-06-03',
                'raid_contact' => 'test@example.com',
                'raid_street' => '123 Street',
                'raid_city' => 'City',
                'raid_postal_code' => '12345',
                'raid_number' => 2026001,
            ];

            unset($raidData[$field]);

            $response = $this->actingAs($this->clubLeader)
                ->post(route('raids.store'), $raidData);

            $response->assertSessionHasErrors($field);
        }
    }

    /**
     * Test that the responsable (adh_id) must exist in members table
     */
    public function test_raid_creation_validates_responsable_exists(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => 99999, // Non-existent member
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('adh_id');
    }

    /**
     * Test that inscription start date must be before raid start date
     */
    public function test_inscription_start_date_must_be_before_raid_start(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-06-02', // After raid start
            'ins_end_date' => '2026-06-03',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-05',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('ins_start_date');
    }

    /**
     * Test that inscription end date must be before raid start date
     */
    public function test_inscription_end_date_must_be_before_raid_start(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-06-02', // After raid start
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-05',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that inscription end date must be after inscription start date
     */
    public function test_inscription_end_date_must_be_after_start_date(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-05-01',
            'ins_end_date' => '2026-04-01', // Before start date
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-05',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that raid end date must be after raid start date
     */
    public function test_raid_end_date_must_be_after_start_date(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-05',
            'raid_date_end' => '2026-06-01', // Before start date
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('raid_date_end');
    }

    /**
     * Test that the club is automatically assigned from the authenticated user
     */
    public function test_club_is_auto_assigned_from_auth_user(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid Auto Club',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 2026001,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertRedirect(route('raids.index'));

        $raid = Raid::where('raid_name', 'Test Raid Auto Club')->first();
        $this->assertNotNull($raid);
        $this->assertEquals($this->clubId, $raid->clu_id);
    }

    /**
     * Test that registration period is created automatically with the raid
     */
    public function test_registration_period_is_created_automatically(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid Period',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 2026002,
        ];

        $initialPeriodCount = RegistrationPeriod::count();

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertRedirect(route('raids.index'));

        // Verify a new registration period was created
        $this->assertEquals($initialPeriodCount + 1, RegistrationPeriod::count());

        $raid = Raid::where('raid_name', 'Test Raid Period')->first();
        $period = RegistrationPeriod::find($raid->ins_id);

        $this->assertNotNull($period);
        $this->assertEquals('2026-03-01', $period->ins_start_date->format('Y-m-d'));
        $this->assertEquals('2026-05-31', $period->ins_end_date->format('Y-m-d'));
    }

    /**
     * Test that email validation works for raid contact
     */
    public function test_raid_contact_must_be_valid_email(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'not-an-email',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 2026003,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('raid_contact');
    }

    /**
     * Test that URL validation works for raid site URL (optional field)
     */
    public function test_raid_site_url_must_be_valid_when_provided(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_site_url' => 'not-a-url',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertSessionHasErrors('raid_site_url');
    }

    /**
     * Test that a club member (non-leader) can be selected as responsable
     */
    public function test_club_member_can_be_responsable(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid Member Responsable',
            'raid_description' => 'Description',
            'adh_id' => $this->regularMember->adh_id, // Using club member, not leader
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => '12345',
            'raid_number' => 2026004,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        $response->assertRedirect(route('raids.index'));
        $this->assertDatabaseHas('raids', [
            'raid_name' => 'Test Raid Member Responsable',
            'adh_id' => $this->regularMember->adh_id,
        ]);
    }

    /**
     * Test that postal code validation works
     */
    public function test_postal_code_must_be_string(): void
    {
        $raidData = [
            'raid_name' => 'Test Raid',
            'raid_description' => 'Description',
            'adh_id' => $this->leaderMember->adh_id,
            'ins_start_date' => '2026-03-01',
            'ins_end_date' => '2026-05-31',
            'raid_date_start' => '2026-06-01',
            'raid_date_end' => '2026-06-03',
            'raid_contact' => 'test@example.com',
            'raid_street' => '123 Street',
            'raid_city' => 'City',
            'raid_postal_code' => 12345, // Integer instead of string
            'raid_number' => 2026005,
        ];

        $response = $this->actingAs($this->clubLeader)
            ->post(route('raids.store'), $raidData);

        // Should convert to string automatically, so this should succeed
        $response->assertRedirect(route('raids.index'));
    }
}
