<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\Team;
use App\Models\Raid;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RaceManagementApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting managed races.
     */
    public function test_can_get_managed_races(): void
    {
        $user = User::factory()->create(['adh_id' => 123]);
        $member = Member::create(['adh_id' => 123, 'adh_license' => 'LIC123']);
        Sanctum::actingAs($user);

        // Race owned directly
        $ownedRace = Race::factory()->create(['adh_id' => 123, 'race_name' => 'Owned Race']);

        // Race not owned
        Race::factory()->create(['adh_id' => 456, 'race_name' => 'Other Race']);

        $response = $this->getJson('/api/me/managed-races');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.race_name', 'Owned Race');
    }

    /**
     * Test getting race participants.
     */
    public function test_can_get_race_participants(): void
    {
        $user = User::factory()->create(['adh_id' => 123]);
        Sanctum::actingAs($user);

        $race = Race::factory()->create(['adh_id' => 123]);
        $participant = User::factory()->create();
        $team = Team::factory()->create();

        RaceRegistration::create([
            'race_id' => $race->race_id,
            'user_id' => $participant->id,
            'equ_id' => $team->equ_id,
            'status' => 'confirmed',
            'reg_date' => now(),
        ]);

        $response = $this->getJson("/api/races/{$race->race_id}/participants");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.user_id', $participant->id);
    }

    /**
     * Test forbidden access to participants of non-managed race.
     */
    public function test_cannot_get_participants_of_non_managed_race(): void
    {
        $user = User::factory()->create(['adh_id' => 123]);
        Sanctum::actingAs($user);

        $race = Race::factory()->create(['adh_id' => 456]);

        $response = $this->getJson("/api/races/{$race->race_id}/participants");

        $response->assertStatus(403);
    }

    /**
     * Test document validation.
     */
    public function test_can_validate_documents(): void
    {
        $user = User::factory()->create(['adh_id' => 123]);
        Sanctum::actingAs($user);

        $race = Race::factory()->create(['adh_id' => 123]);
        $registration = RaceRegistration::create([
            'race_id' => $race->race_id,
            'user_id' => User::factory()->create()->id,
            'equ_id' => Team::factory()->create()->equ_id,
            'status' => 'pending',
            'reg_date' => now(),
        ]);

        $response = $this->patchJson("/api/registrations/{$registration->reg_id}/validate-docs", [
            'status' => 'confirmed',
            'admin_notes' => 'All good'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('confirmed', $registration->fresh()->status);
    }
}
