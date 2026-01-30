<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Race;
use App\Models\Registration;
use App\Models\RaceParticipant;
use App\Models\Team;
use App\Models\Raid;
use App\Models\Member;
use App\Models\MedicalDoc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RaceManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Setup permissions needed for policies
        Permission::findOrCreate('edit-own-race', 'web');
    }

    private function createDependencies()
    {
        $pay_id = DB::table('inscriptions_payment')->insertGetId([
            'pai_date' => now(),
            'pai_is_paid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc = MedicalDoc::factory()->create();

        return [$pay_id, $doc->doc_id];
    }

    /**
     * Test getting managed races.
     */
    public function test_can_get_managed_races(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['adh_id' => $member->adh_id]);
        $user->givePermissionTo('edit-own-race');
        Sanctum::actingAs($user);

        // Race owned directly
        Race::factory()->create(['adh_id' => $member->adh_id, 'race_name' => 'Owned Race']);

        // Race not owned
        Race::factory()->create(['adh_id' => $member->adh_id + 1, 'race_name' => 'Other Race']);

        $response = $this->getJson('/api/me/managed-races');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Owned Race', $data[0]['race_name']);
    }

    /**
     * Test getting race participants.
     */
    public function test_can_get_race_participants(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['adh_id' => $member->adh_id]);
        $user->givePermissionTo('edit-own-race');
        Sanctum::actingAs($user);

        $race = Race::factory()->create(['adh_id' => $member->adh_id]);
        $participantUser = User::factory()->create();
        $team = Team::factory()->create();

        [$pay_id, $doc_id] = $this->createDependencies();

        // Create registration manually
        $registration = Registration::create([
            'race_id' => $race->race_id,
            'equ_id' => $team->equ_id,
            'pay_id' => $pay_id,
            'doc_id' => $doc_id,
            'reg_validated' => true,
        ]);

        // Create participant
        RaceParticipant::create([
            'reg_id' => $registration->reg_id,
            'user_id' => $participantUser->id,
            'pps_number' => 'PPS123',
        ]);

        $response = $this->getJson("/api/races/{$race->race_id}/participants");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals($participantUser->id, $data[0]['user_id']);
    }

    /**
     * Test forbidden access to participants of non-managed race.
     */
    public function test_cannot_get_participants_of_non_managed_race(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['adh_id' => $member->adh_id]);
        $user->givePermissionTo('edit-own-race');
        Sanctum::actingAs($user);

        $otherMember = Member::factory()->create();
        $race = Race::factory()->create(['adh_id' => $otherMember->adh_id]);

        $response = $this->getJson("/api/races/{$race->race_id}/participants");

        $response->assertStatus(403);
    }

    /**
     * Test document validation.
     */
    public function test_can_validate_documents(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['adh_id' => $member->adh_id]);
        $user->givePermissionTo('edit-own-race');
        Sanctum::actingAs($user);

        $race = Race::factory()->create(['adh_id' => $member->adh_id]);

        [$pay_id, $doc_id] = $this->createDependencies();

        $registration = Registration::create([
            'race_id' => $race->race_id,
            'equ_id' => Team::factory()->create()->equ_id,
            'pay_id' => $pay_id,
            'doc_id' => $doc_id,
            'reg_validated' => false,
        ]);

        $response = $this->patchJson("/api/registrations/{$registration->reg_id}/validate-docs", [
            'status' => 'confirmed',
            'admin_notes' => 'All good'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue((bool) $registration->fresh()->reg_validated);
    }
}
