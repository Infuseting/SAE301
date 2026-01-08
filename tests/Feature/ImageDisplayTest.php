<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Club;
use App\Models\Member;
use App\Models\MedicalDoc;
use App\Models\Raid;
use App\Models\Race;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test suite for Image Display functionality
 * 
 * Tests cover:
 * - Images are returned with correct /storage/ prefix in controller responses
 * - WelcomeController returns raid images with /storage/ prefix
 * - MyRaidController returns raid images with /storage/ prefix
 * - MyRaceController returns race images with /storage/ prefix
 * - PublicProfileController returns team images with /storage/ prefix
 * - TeamController returns team images with /storage/ prefix
 * - Null images are handled correctly (fallback or null)
 */
class ImageDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $clubLeader;
    private User $regularUser;
    private Club $club;
    private Member $member;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure fake storage
        Storage::fake('public');

        // Create medical document
        $medicalDoc = MedicalDoc::factory()->create();

        // Create member
        $this->member = Member::factory()->create();

        // Create club leader user with member
        $this->clubLeader = User::factory()->create([
            'doc_id' => $medicalDoc->doc_id,
            'adh_id' => $this->member->adh_id,
        ]);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'doc_id' => $medicalDoc->doc_id,
        ]);

        // Assign responsable-club role
        $this->clubLeader->assignRole('responsable-club');

        // Create club owned by the leader
        $this->club = Club::factory()->create([
            'created_by' => $this->clubLeader->id,
        ]);

        // Add leader to club as manager
        $this->club->allMembers()->attach($this->clubLeader->id, [
            'role' => 'manager',
            'status' => 'approved',
        ]);
    }

    /**
     * Helper: Create a raid with an image
     */
    private function createRaidWithImage(string $filename = 'raid.jpg', ?string $imagePath = null): Raid
    {
        $image = UploadedFile::fake()->create($filename, 1000, 'image/jpeg');
        $path = $imagePath ?? 'raids/' . $image->hashName();
        Storage::disk('public')->put($path, file_get_contents($image));

        return Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
            'raid_image' => $path,
            'raid_date_start' => now()->addDays(10),
            'raid_date_end' => now()->addDays(12),
        ]);
    }

    /**
     * Helper: Create a race with an image
     */
    private function createRaceWithImage(int $raidId, string $filename = 'race.jpg'): Race
    {
        $image = UploadedFile::fake()->create($filename, 1000, 'image/jpeg');
        $path = 'races/' . $image->hashName();
        Storage::disk('public')->put($path, file_get_contents($image));

        return Race::factory()->create([
            'raid_id' => $raidId,
            'image_url' => $path,
        ]);
    }

    /**
     * Helper: Create a team with an image
     */
    private function createTeamWithImage(string $filename = 'team.jpg'): Team
    {
        $image = UploadedFile::fake()->create($filename, 1000, 'image/jpeg');
        $path = 'teams/' . $image->hashName();
        Storage::disk('public')->put($path, file_get_contents($image));

        return Team::factory()->create([
            'equ_name' => 'Test Team',
            'equ_image' => $path,
            'adh_id' => $this->member->adh_id,
        ]);
    }

    /**
     * Helper: Create a time record for a race and user
     */
    private function createTimeRecord(Race $race, User $user): void
    {
        $race->times()->create([
            'user_id' => $user->id,
            'time_hours' => 1,
            'time_minutes' => 30,
            'time_seconds' => 45,
            'time_total_seconds' => 5445,
            'time_rank' => 1,
            'time_rank_start' => 1,
        ]);
    }

    /**
     * Helper: Get Inertia props from response
     */
    private function getInertiaProps($response): array
    {
        return $response->viewData('page')['props'];
    }

    /**
     * Helper: Assert image has /storage/ prefix and correct folder
     */
    private function assertImageHasStoragePrefix(?string $imagePath, string $folder): void
    {
        $this->assertNotNull($imagePath);
        $this->assertStringStartsWith('/storage/', $imagePath);
        $this->assertStringContainsString($folder . '/', $imagePath);
    }

    /**
     * Test 1: WelcomeController returns raid images with /storage/ prefix
     */
    public function test_welcome_controller_returns_raid_images_with_storage_prefix(): void
    {
        $raid = $this->createRaidWithImage();

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $raidData = collect($props['upcomingRaids'])->firstWhere('id', $raid->raid_id);
        
        $this->assertNotNull($raidData);
        $this->assertImageHasStoragePrefix($raidData['image'], 'raids');
    }

    /**
     * Test 2: WelcomeController handles null raid images correctly
     */
    public function test_welcome_controller_handles_null_raid_images(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
            'raid_image' => null,
            'raid_date_start' => now()->addDays(10),
            'raid_date_end' => now()->addDays(12),
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $raidData = collect($props['upcomingRaids'])->firstWhere('id', $raid->raid_id);
        
        $this->assertNotNull($raidData);
        $this->assertTrue(
            is_null($raidData['image']) || str_starts_with($raidData['image'], 'http')
        );
    }

    /**
     * Test 3: MyRaidController returns raid images with /storage/ prefix
     */
    public function test_my_raid_controller_returns_raid_images_with_storage_prefix(): void
    {
        $raid = $this->createRaidWithImage();
        $race = Race::factory()->create(['raid_id' => $raid->raid_id]);
        $this->createTimeRecord($race, $this->regularUser);

        $response = $this->actingAs($this->regularUser)->get(route('myraid.index'));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $raidData = collect($props['raids'])->firstWhere('id', $raid->raid_id);
        
        $this->assertNotNull($raidData);
        $this->assertImageHasStoragePrefix($raidData['image'], 'raids');
    }

    /**
     * Test 4: MyRaceController returns race images with /storage/ prefix
     */
    public function test_my_race_controller_returns_race_images_with_storage_prefix(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
        ]);
        $race = $this->createRaceWithImage($raid->raid_id);
        $this->createTimeRecord($race, $this->regularUser);

        $response = $this->actingAs($this->regularUser)->get(route('myrace.index'));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $raceData = collect($props['races'])->firstWhere('id', $race->race_id);
        
        $this->assertNotNull($raceData);
        $this->assertImageHasStoragePrefix($raceData['image'], 'races');
    }

    /**
     * Test 5: MyRaceController handles null race images correctly
     */
    public function test_my_race_controller_handles_null_race_images(): void
    {
        $raid = Raid::factory()->create([
            'clu_id' => $this->club->club_id,
            'adh_id' => $this->member->adh_id,
        ]);
        $race = Race::factory()->create([
            'raid_id' => $raid->raid_id,
            'image_url' => null,
        ]);
        $this->createTimeRecord($race, $this->regularUser);

        $response = $this->actingAs($this->regularUser)->get(route('myrace.index'));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $raceData = collect($props['races'])->firstWhere('id', $race->race_id);
        
        $this->assertNotNull($raceData);
        $this->assertNull($raceData['image']);
    }

    /**
     * Test 6: PublicProfileController returns team images with /storage/ prefix
     */
    public function test_public_profile_controller_returns_team_images_with_storage_prefix(): void
    {
        $team = $this->createTeamWithImage();
        $team->users()->attach($this->regularUser->id);

        $response = $this->actingAs($this->regularUser)
            ->get(route('profile.show', $this->regularUser));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $teamData = collect($props['teams'])->firstWhere('id', $team->equ_id);
        
        $this->assertNotNull($teamData);
        $this->assertImageHasStoragePrefix($teamData['image'], 'teams');
    }

    /**
     * Test 7: PublicProfileController handles null team images correctly
     */
    public function test_public_profile_controller_handles_null_team_images(): void
    {
        $team = Team::factory()->create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'adh_id' => $this->member->adh_id,
        ]);
        $team->users()->attach($this->regularUser->id);

        $response = $this->actingAs($this->regularUser)
            ->get(route('profile.show', $this->regularUser));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $teamData = collect($props['teams'])->firstWhere('id', $team->equ_id);
        
        $this->assertNotNull($teamData);
        $this->assertNull($teamData['image']);
    }

    /**
     * Test 8: TeamController show returns team image with /storage/ prefix
     */
    public function test_team_controller_show_returns_team_image_with_storage_prefix(): void
    {
        $team = $this->createTeamWithImage();

        $response = $this->actingAs($this->regularUser)
            ->get(route('teams.show', $team->equ_id));

        $response->assertStatus(200);
        
        $props = $response->viewData('page')['props'];
        $teamData = $props['team'];
        $this->assertNotNull($teamData);
        $this->assertArrayHasKey('image', $teamData);
        $this->assertStringStartsWith('/storage/', $teamData['image']);
        $this->assertStringContainsString('teams/', $teamData['image']);
    }

    /**
     * Test 9: TeamController show method handles null team images correctly
     */
    public function test_team_controller_show_handles_null_team_images(): void
    {
        // Create team without image
        $team = Team::factory()->create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'adh_id' => $this->member->adh_id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('teams.show', $team->equ_id));
        $response->assertStatus(200);
        
        $props = $this->getInertiaProps($response);
        $this->assertNull($props['team']['image']);
    }

    /**
     * Test 10: Multiple entities with images all have correct /storage/ prefix
     */
    public function test_multiple_entities_with_images_have_correct_storage_prefix(): void
    {
        $raid = $this->createRaidWithImage('raid.jpg');
        $team = $this->createTeamWithImage('team.jpg');

        // Test WelcomeController
        $response = $this->get(route('home'));
        $props = $this->getInertiaProps($response);
        $raidData = collect($props['upcomingRaids'])->firstWhere('id', $raid->raid_id);
        $this->assertImageHasStoragePrefix($raidData['image'], 'raids');

        // Test TeamController
        $response = $this->actingAs($this->regularUser)
            ->get(route('teams.show', $team->equ_id));
        $props = $this->getInertiaProps($response);
        $this->assertImageHasStoragePrefix($props['team']['image'], 'teams');
    }

    /**
     * Test 11: Image paths do not have double slashes
     */
    public function test_image_paths_do_not_have_double_slashes(): void
    {
        $raid = $this->createRaidWithImage();

        $response = $this->get(route('home'));
        $props = $this->getInertiaProps($response);
        $raidData = collect($props['upcomingRaids'])->firstWhere('id', $raid->raid_id);
        
        // Check no double slashes in path
        $this->assertStringNotContainsString('//', str_replace('http://', '', $raidData['image']));
        $this->assertStringNotContainsString('//', str_replace('https://', '', $raidData['image']));
    }

    /**
     * Test 12: Verify image files actually exist in storage
     */
    public function test_image_files_actually_exist_in_storage(): void
    {
        $raid = $this->createRaidWithImage();

        // Verify file exists
        Storage::disk('public')->assertExists($raid->raid_image);
        
        // Verify path is correct
        $this->assertStringStartsWith('raids/', $raid->raid_image);
        
        // Get from controller
        $response = $this->get(route('raids.show', $raid->raid_id));
        $response->assertStatus(200);
    }
}
