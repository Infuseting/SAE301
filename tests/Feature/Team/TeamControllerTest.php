<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ========================================
    // BASIC CREATION TESTS
    // ========================================

    /**
     * Test team creation without image - creator participates.
     */
    public function test_create_team_without_image_creator_participates(): void
    {
        $creator = User::factory()->create();
        $teammate1 = User::factory()->create();
        $teammate2 = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Test Team',
            'image' => null,
            'teammates' => [
                ['id' => $teammate1->id],
                ['id' => $teammate2->id],
            ],
            'join_team' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Équipe créée avec succès!');

        // Team created with creator as leader
        $this->assertDatabaseHas('teams', [
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'user_id' => $creator->id,
        ]);

        // All 3 users attached (creator + 2 teammates)
        $team = Team::where('equ_name', 'Test Team')->first();
        $this->assertEquals(3, $team->users()->count());
        $this->assertTrue($team->users()->where('users.id', $creator->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $teammate1->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $teammate2->id)->exists());
    }

    /**
     * Test team creation without image - creator does not participate.
     */
    public function test_create_team_without_image_creator_does_not_participate(): void
    {
        $creator = User::factory()->create();
        $teammate1 = User::factory()->create();
        $teammate2 = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team Without Creator',
            'image' => null,
            'teammates' => [
                ['id' => $teammate1->id],
                ['id' => $teammate2->id],
            ],
            'join_team' => false,
        ]);

        $response->assertRedirect(route('dashboard'));

        // Team created with creator as leader
        $team = Team::where('equ_name', 'Team Without Creator')->first();
        $this->assertEquals($creator->id, $team->user_id);

        // Only 2 teammates attached (creator not in participants)
        $this->assertEquals(2, $team->users()->count());
        $this->assertFalse($team->users()->where('users.id', $creator->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $teammate1->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $teammate2->id)->exists());
    }

    /**
     * Test team creation with image.
     */
    public function test_create_team_with_image(): void
    {
        // Skip if GD is not available
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        $creator = User::factory()->create();
        $teammate = User::factory()->create();

        $image = UploadedFile::fake()->image('team-logo.jpg', 100, 100);

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with Image',
            'image' => $image,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        $team = Team::where('equ_name', 'Team with Image')->first();
        $this->assertNotNull($team);
        $this->assertNotNull($team->equ_image);
        $this->assertStringContainsString('teams/', $team->equ_image);

        Storage::disk('public')->assertExists($team->equ_image);

        // Creator and teammate attached
        $this->assertEquals(2, $team->users()->count());
    }

    /**
     * Test team creation with only creator participating (no teammates).
     */
    public function test_create_team_with_only_creator(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Solo Team',
            'image' => null,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        $team = Team::where('equ_name', 'Solo Team')->first();
        $this->assertEquals($creator->id, $team->user_id);
        $this->assertEquals(1, $team->users()->count());
        $this->assertTrue($team->users()->where('users.id', $creator->id)->exists());
    }

    // ========================================
    // TESTS DE VALIDATION - PARTICIPANTS
    // ========================================

    /**
     * Test team creation fails without any participants.
     */
    public function test_create_team_fails_without_participants(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Empty Team',
            'image' => null,
            'teammates' => [],
            'join_team' => false,
        ]);

        $response->assertSessionHasErrors('teammates');

        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Empty Team',
        ]);
    }

    /**
     * Test team creation with invalid teammate_id.
     */
    public function test_create_team_fails_with_invalid_teammate_id(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Invalid Team',
            'image' => null,
            'teammates' => [
                ['id' => 99999], // Non-existent user
            ],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('teammates.0.id');

        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Invalid Team',
        ]);
    }

    // ========================================
    // TESTS DE VALIDATION - NOM
    // ========================================

    /**
     * Test team creation fails without name.
     */
    public function test_create_team_fails_without_name(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => '',
            'image' => null,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test team creation fails with name too long.
     */
    public function test_create_team_fails_with_name_too_long(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => str_repeat('a', 256), // 256 characters
            'image' => null,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ========================================
    // TESTS DE VALIDATION - IMAGE
    // ========================================

    /**
     * Test team creation with large image fails.
     */
    public function test_create_team_fails_with_oversized_image(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create();

        $image = UploadedFile::fake()->create('large-image.jpg', 3000, 'image/jpeg');

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with Large Image',
            'image' => $image,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Team with Large Image',
        ]);
    }

    /**
     * Test team creation with invalid image file type.
     */
    public function test_create_team_fails_with_invalid_image_type(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with PDF',
            'image' => $file,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Team with PDF',
        ]);
    }

    // ========================================
    // TESTS TABLE HAS_PARTICIPATE
    // ========================================

    /**
     * Test has_participate table inserts correctly.
     */
    public function test_has_participate_table_inserts_correctly(): void
    {
        $creator = User::factory()->create();
        $teammate = User::factory()->create();

        $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Table Test Team',
            'image' => null,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => true,
        ]);

        $team = Team::where('equ_name', 'Table Test Team')->first();

        $this->assertDatabaseHas('has_participate', [
            'equ_id' => $team->equ_id,
            'id_users' => $creator->id,
        ]);

        $this->assertDatabaseHas('has_participate', [
            'equ_id' => $team->equ_id,
            'id_users' => $teammate->id,
        ]);

        $this->assertEquals(2, $team->users()->count());
    }

    /**
     * Test that duplicate teammates are not inserted.
     */
    public function test_duplicate_teammates_not_inserted(): void
    {
        $creator = User::factory()->create();
        $teammate = User::factory()->create();

        $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team Duplicate Test',
            'image' => null,
            'teammates' => [
                ['id' => $teammate->id],
                ['id' => $teammate->id], // Same teammate added twice
            ],
            'join_team' => true,
        ]);

        $team = Team::where('equ_name', 'Team Duplicate Test')->first();

        // Teammate should appear only once
        $teammateCount = $team->users()->where('users.id', $teammate->id)->count();
        $this->assertEquals(1, $teammateCount);

        // Team should have 2 users (creator + teammate), not 3
        $this->assertEquals(2, $team->users()->count());
    }

    /**
     * Test creator not duplicated if also in teammates.
     */
    public function test_creator_not_duplicated_in_participants(): void
    {
        $creator = User::factory()->create();
        $teammate = User::factory()->create();

        $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Creator Duplicate Test',
            'image' => null,
            'teammates' => [
                ['id' => $teammate->id],
                ['id' => $creator->id], // Creator trying to add themselves as teammate
            ],
            'join_team' => true,
        ]);

        $team = Team::where('equ_name', 'Creator Duplicate Test')->first();

        // Creator should appear only once
        $creatorCount = $team->users()->where('users.id', $creator->id)->count();
        $this->assertEquals(1, $creatorCount);
    }

    // ========================================
    // TESTS LEADER (user_id)
    // ========================================

    /**
     * Test creator is always set as leader.
     */
    public function test_creator_is_always_leader(): void
    {
        $creator = User::factory()->create();
        $teammate = User::factory()->create();

        $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Leader Test Team',
            'image' => null,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => false,
        ]);

        $team = Team::where('equ_name', 'Leader Test Team')->first();

        // Creator should be the team leader
        $this->assertEquals($creator->id, $team->user_id);
        $this->assertEquals($creator->id, $team->leader->id);
    }

    // ========================================
    // TESTS AUTHENTIFICATION
    // ========================================

    /**
     * Test unauthenticated user cannot create team.
     */
    public function test_unauthenticated_user_cannot_create_team(): void
    {
        $response = $this->post(route('team.store'), [
            'name' => 'Unauthorized Team',
            'image' => null,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Unauthorized Team',
        ]);
    }

    /**
     * Test create team page is accessible to authenticated user.
     */
    public function test_create_team_page_accessible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('team.create'));

        $response->assertStatus(200);
    }

    /**
     * Test create team page is not accessible to unauthenticated user.
     */
    public function test_create_team_page_not_accessible_to_guest(): void
    {
        $response = $this->get(route('team.create'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // TESTS WITH MULTIPLE TEAMMATES
    // ========================================

    /**
     * Test team creation with many teammates.
     */
    public function test_create_team_with_many_teammates(): void
    {
        $creator = User::factory()->create();
        $teammates = User::factory()->count(5)->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Big Team',
            'image' => null,
            'teammates' => $teammates->map(fn($t) => ['id' => $t->id])->toArray(),
            'join_team' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        $team = Team::where('equ_name', 'Big Team')->first();
        
        // 6 users total (creator + 5 teammates)
        $this->assertEquals(6, $team->users()->count());
    }

    /**
     * Test team creation with empty teammates array but creator participates.
     */
    public function test_create_team_with_empty_teammates_creator_joins(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Creator Only Team',
            'image' => null,
            'teammates' => [],
            'join_team' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        $team = Team::where('equ_name', 'Creator Only Team')->first();
        $this->assertEquals(1, $team->users()->count());
        $this->assertTrue($team->users()->where('users.id', $creator->id)->exists());
    }
}
