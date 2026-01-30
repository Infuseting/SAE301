<?php

namespace Tests\Feature\Api;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_access_club_details_via_api()
    {
        $club = Club::factory()->create([
            'club_name' => 'API Test Club',
            'is_approved' => true
        ]);

        $response = $this->getJson("/api/clubs/{$club->club_id}");

        // This should now PASS
        $response->assertStatus(200)
            ->assertJsonPath('club.club_name', 'API Test Club');
    }

    public function test_public_can_access_club_list_via_api()
    {
        Club::factory()->create(['is_approved' => true]);

        $response = $this->getJson("/api/clubs");

        // This is expected to FAIL with 401 currently
        $response->assertStatus(200);
    }
}
