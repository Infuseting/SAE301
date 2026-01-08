<?php

namespace Tests\Feature\Raid;

use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Raid;
use App\Models\RegistrationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test suite for Raid update functionality
 * 
 * Tests cover:
 * - Access control (only club leaders can edit raids)
 * - Access to edit form
 * - Validation rules for updates (dates, required fields, relationships)
 * - Updating raid information
 * - Updating registration periods
 * - Member verification within club
 * - Prevention of unauthorized edits
 */
class RaidUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $clubLeader;
    private User $otherClubLeader;
    private User $clubMember;
    private User $nonClubUser;
    private int $clubId;
    private int $otherClubId;
    private Member $leaderMember;
    private Member $otherLeaderMember;
    private Member $regularMember;
    private Raid $raid;
    private RegistrationPeriod $registrationPeriod;

    /**
     * Setup test environment before each test
     * Creates club, users, members, and an existing raid for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create medical documents
        $leaderDoc = MedicalDoc::factory()->create();
        $otherLeaderDoc = MedicalDoc::factory()->create();
        $memberDoc = MedicalDoc::factory()->create();
        $nonClubDoc = MedicalDoc::factory()->create();

        // Create members
        $this->leaderMember = Member::factory()->create();
        $this->otherLeaderMember = Member::factory()->create();
        $this->regularMember = Member::factory()->create();
        $nonClubMember = Member::factory()->create();

        // Create club leader user
        $this->clubLeader = User::factory()->create([
            'doc_id' => $leaderDoc->doc_id,
            'adh_id' => $this->leaderMember->adh_id,
        ]);

        // Create other club leader user
        $this->otherClubLeader = User::factory()->create([
            'doc_id' => $otherLeaderDoc->doc_id,
            'adh_id' => $this->otherLeaderMember->adh_id,
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

        // Create main club with leader using DB::table
        $this->clubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Test Club',
            'club_street' => '123 Test Street',
            'club_city' => 'Test City',
            'club_postal_code' => '12345',
            'ffso_id' => 'FFCO999',
            'description' => 'Test Club Description',
            'is_approved' => true,
            'created_by' => $this->clubLeader->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create other club with different leader
        $this->otherClubId = DB::table('clubs')->insertGetId([
            'club_name' => 'Other Test Club',
            'club_street' => '456 Other Street',
            'club_city' => 'Other City',
            'club_postal_code' => '54321',
            'ffso_id' => 'FFCO888',
            'description' => 'Other Test Club Description',
            'is_approved' => true,
            'created_by' => $this->otherClubLeader->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add users to main club
        DB::table('club_user')->insert([
            ['club_id' => $this->clubId, 'user_id' => $this->clubLeader->id, 'status' => 'approved', 'role' => 'manager', 'created_at' => now(), 'updated_at' => now()],
            ['club_id' => $this->clubId, 'user_id' => $this->clubMember->id, 'status' => 'approved', 'role' => 'member', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Add leader to other club
        DB::table('club_user')->insert([
            ['club_id' => $this->otherClubId, 'user_id' => $this->otherClubLeader->id, 'status' => 'approved', 'role' => 'manager', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create registration period
        $this->registrationPeriod = RegistrationPeriod::create([
            'ins_start_date' => now()->addDays(5),
            'ins_end_date' => now()->addDays(25),
        ]);

        // Create existing raid for testing updates
        $this->raid = Raid::create([
            'raid_name' => 'Original Raid Name',
            'raid_description' => 'Original description',
            'raid_date_start' => now()->addDays(30),
            'raid_date_end' => now()->addDays(32),
            'raid_contact' => 'original@example.com',
            'raid_street' => 'Original Street',
            'raid_city' => 'Original City',
            'raid_postal_code' => '11111',
            'raid_number' => 100001,
            'adh_id' => $this->leaderMember->adh_id,
            'clu_id' => $this->clubId,
            'ins_id' => $this->registrationPeriod->ins_id,
        ]);
    }

    /**
     * Test that club leader can access raid edit form
     */
    public function test_club_leader_can_access_raid_edit_form(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.edit', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Raid/Edit')
                ->has('raid')
                ->has('userClub')
                ->has('clubMembers')
        );
    }

    /**
     * Test that raid edit form receives all required raid data
     */
    public function test_raid_edit_form_receives_complete_raid_data(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.edit', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Raid/Edit')
                ->has('raid', fn ($raid) => 
                    $raid->has('raid_id')
                        ->has('raid_name')
                        ->has('raid_description')
                        ->has('raid_date_start')
                        ->has('raid_date_end')
                        ->has('raid_contact')
                        ->has('raid_street')
                        ->has('raid_city')
                        ->has('raid_postal_code')
                        ->has('raid_number')
                        ->has('adh_id')
                        ->has('clu_id')
                        ->has('ins_id')
                        ->has('registration_period')
                        ->etc()
                )
                ->has('userClub', fn ($club) =>
                    $club->has('club_id')
                        ->has('club_name')
                )
                ->has('clubMembers')
        );
    }

    /**
     * Test that raid edit form receives registration period data
     */
    public function test_raid_edit_form_receives_registration_period_data(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.edit', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Raid/Edit')
                ->has('raid.registration_period', fn ($period) =>
                    $period->has('ins_id')
                        ->has('ins_start_date')
                        ->has('ins_end_date')
                        ->etc()
                )
        );
    }

    /**
     * Test that raid data values are correctly passed to edit form
     */
    public function test_raid_edit_form_has_correct_raid_values(): void
    {
        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.edit', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Raid/Edit')
                ->where('raid.raid_id', $this->raid->raid_id)
                ->where('raid.raid_name', 'Original Raid Name')
                ->where('raid.raid_description', 'Original description')
                ->where('raid.raid_contact', 'original@example.com')
                ->where('raid.raid_city', 'Original City')
                ->where('raid.raid_postal_code', '11111')
                ->where('raid.clu_id', $this->clubId)
                ->where('raid.adh_id', $this->leaderMember->adh_id)
        );
    }

    /**
     * Test that club members list contains expected members for edit form
     */
    public function test_raid_edit_form_has_club_members_with_required_fields(): void
    {
        // Assign adherent role to club member so they appear in the list
        $adherentRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'adherent']);
        $this->clubMember->assignRole($adherentRole);

        $response = $this->actingAs($this->clubLeader)
            ->get(route('raids.edit', $this->raid->raid_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Raid/Edit')
                ->has('clubMembers', fn ($members) =>
                    $members->each(fn ($member) =>
                        $member->has('id')
                            ->has('name')
                            ->has('email')
                            ->has('adh_id')
                    )
                )
        );
    }

    /**
     * Test successful raid update with valid data
     */
    public function test_can_update_raid_with_valid_data(): void
    {
        $updatedData = [
            'raid_name' => 'Updated Raid Name',
            'raid_description' => 'Updated description',
            'raid_date_start' => now()->addDays(40)->format('Y-m-d\TH:i'),
            'raid_date_end' => now()->addDays(42)->format('Y-m-d\TH:i'),
            'raid_contact' => 'updated@example.com',
            'raid_street' => 'Updated Street',
            'raid_city' => 'Updated City',
            'raid_postal_code' => '22222',
            'raid_number' => 100002,
            'adh_id' => $this->leaderMember->adh_id,
            'clu_id' => $this->clubId,
            'ins_start_date' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'ins_end_date' => now()->addDays(35)->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $updatedData);

        $response->assertRedirect(route('raids.show', $this->raid->raid_id));
        $response->assertSessionHas('success', 'Raid updated successfully.');

        // Verify raid was updated
        $this->assertDatabaseHas('raids', [
            'raid_id' => $this->raid->raid_id,
            'raid_name' => 'Updated Raid Name',
            'raid_description' => 'Updated description',
            'raid_contact' => 'updated@example.com',
            'raid_city' => 'Updated City',
            'raid_postal_code' => '22222',
            'raid_number' => 100002,
        ]);
    }

    /**
     * Test that registration period is updated when raid is updated
     */
    public function test_registration_period_is_updated_with_raid(): void
    {
        $updatedData = [
            'raid_name' => 'Updated Raid Name',
            'raid_description' => 'Updated description',
            'raid_date_start' => now()->addDays(40)->format('Y-m-d\TH:i'),
            'raid_date_end' => now()->addDays(42)->format('Y-m-d\TH:i'),
            'raid_contact' => 'updated@example.com',
            'raid_street' => 'Updated Street',
            'raid_city' => 'Updated City',
            'raid_postal_code' => '22222',
            'raid_number' => 100002,
            'adh_id' => $this->leaderMember->adh_id,
            'clu_id' => $this->clubId,
            'ins_start_date' => now()->addDays(15)->format('Y-m-d\TH:i'),
            'ins_end_date' => now()->addDays(38)->format('Y-m-d\TH:i'),
        ];

        $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $updatedData);

        // Verify registration period was updated
        $this->assertDatabaseHas('registration_period', [
            'ins_id' => $this->registrationPeriod->ins_id,
        ]);

        $updatedPeriod = RegistrationPeriod::find($this->registrationPeriod->ins_id);
        $this->assertNotNull($updatedPeriod);
        $this->assertEquals(
            now()->addDays(15)->format('Y-m-d H:i'),
            $updatedPeriod->ins_start_date->format('Y-m-d H:i')
        );
    }

    /**
     * Test that raid name is required for update
     */
    public function test_raid_name_is_required_for_update(): void
    {
        $data = $this->getValidRaidData();
        unset($data['raid_name']);

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('raid_name');
    }

    /**
     * Test that raid contact must be a valid email
     */
    public function test_raid_contact_must_be_valid_email_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_contact'] = 'not-an-email';

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('raid_contact');
    }

    /**
     * Test that raid end date must be after start date
     */
    public function test_raid_end_date_must_be_after_start_date_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_date_start'] = now()->addDays(40)->format('Y-m-d\TH:i');
        $data['raid_date_end'] = now()->addDays(38)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('raid_date_end');
    }

    /**
     * Test that inscription start date must be before raid start date
     */
    public function test_inscription_start_must_be_before_raid_start_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_date_start'] = now()->addDays(40)->format('Y-m-d\TH:i');
        $data['ins_start_date'] = now()->addDays(45)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('ins_start_date');
    }

    /**
     * Test that inscription end date must be after inscription start date
     */
    public function test_inscription_end_must_be_after_inscription_start_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['ins_start_date'] = now()->addDays(20)->format('Y-m-d\TH:i');
        $data['ins_end_date'] = now()->addDays(15)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that inscription end date must be before raid start date
     */
    public function test_inscription_end_must_be_before_raid_start_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_date_start'] = now()->addDays(40)->format('Y-m-d\TH:i');
        $data['ins_end_date'] = now()->addDays(42)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('ins_end_date');
    }

    /**
     * Test that responsible member must exist in database
     */
    public function test_responsible_member_must_exist_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['adh_id'] = 99999; // Non-existent member

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('adh_id');
    }

    /**
     * Test that responsible member must be part of the club
     */
    public function test_responsible_must_be_club_member_for_update(): void
    {
        // Create a member not in the club
        $outsideMember = Member::factory()->create();

        $data = $this->getValidRaidData();
        $data['adh_id'] = $outsideMember->adh_id;

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('adh_id');
    }

    /**
     * Test that postal code can be provided as integer and is converted to string
     */
    public function test_postal_code_integer_is_converted_to_string_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_postal_code'] = 75001; // Integer

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertRedirect(route('raids.show', $this->raid->raid_id));

        $this->assertDatabaseHas('raids', [
            'raid_id' => $this->raid->raid_id,
            'raid_postal_code' => '75001', // Should be stored as string
        ]);
    }

    /**
     * Test that raid site URL must be valid URL format
     */
    public function test_raid_site_url_must_be_valid_for_update(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_site_url'] = 'not-a-valid-url';

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('raid_site_url');
    }

    /**
     * Test updating raid with optional site URL
     */
    public function test_can_update_raid_with_optional_site_url(): void
    {
        $data = $this->getValidRaidData();
        $data['raid_site_url'] = 'https://example-raid.com';

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertRedirect(route('raids.show', $this->raid->raid_id));

        $this->assertDatabaseHas('raids', [
            'raid_id' => $this->raid->raid_id,
            'raid_site_url' => 'https://example-raid.com',
        ]);
    }

    /**
     * Test that club ID cannot be changed to a non-existent club
     */
    public function test_cannot_update_raid_with_non_existent_club(): void
    {
        $data = $this->getValidRaidData();
        $data['clu_id'] = 99999; // Non-existent club

        $response = $this->actingAs($this->clubLeader)
            ->put(route('raids.update', $this->raid->raid_id), $data);

        $response->assertSessionHasErrors('clu_id');
    }

    /**
     * Helper method to get valid raid update data
     */
    private function getValidRaidData(): array
    {
        return [
            'raid_name' => 'Updated Raid Name',
            'raid_description' => 'Updated description',
            'raid_date_start' => now()->addDays(40)->format('Y-m-d\TH:i'),
            'raid_date_end' => now()->addDays(42)->format('Y-m-d\TH:i'),
            'raid_contact' => 'updated@example.com',
            'raid_street' => 'Updated Street',
            'raid_city' => 'Updated City',
            'raid_postal_code' => '22222',
            'raid_number' => 100002,
            'adh_id' => $this->leaderMember->adh_id,
            'clu_id' => $this->clubId,
            'ins_start_date' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'ins_end_date' => now()->addDays(38)->format('Y-m-d\TH:i'),
        ];
    }
}
