<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\Member;
use App\Models\MedicalDoc;
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

    /**
     * Test team creation without image.
     */
    public function test_create_team_without_image(): void
    {
        // Create users
        $leader = User::factory()->create();
        $teammate1 = User::factory()->create();
        $teammate2 = User::factory()->create();
        $creator = User::factory()->create();

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Test Team',
            'image' => null,
            'leader_id' => $leader->id,
            'teammates' => [
                ['id' => $teammate1->id],
                ['id' => $teammate2->id],
            ],
            'join_team' => false,
        ]);

        // Assert - Redirect and message
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Équipe créée avec succès!');

        // Assert - Team created in database
        $this->assertDatabaseHas('teams', [
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'adh_id' => $leader->id,
        ]);

        // Assert - Users are attached to team
        $team = Team::where('equ_name', 'Test Team')->first();
        $this->assertTrue($team->users()->whereIn('users.id', [
            $leader->id,
            $teammate1->id,
            $teammate2->id,
        ])->count() === 3);
    }

    /**
     * Test team creation with image using file content instead of generating.
     */
    public function test_create_team_with_image(): void
    {
        Storage::fake('public');

        // Create users
        $leader = User::factory()->create();
        $teammate = User::factory()->create();
        $creator = User::factory()->create();

        // Create a fake file that mimics an image
        $image = UploadedFile::fake()->create('team-logo.jpg', 100, 'image/jpeg');

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with Image',
            'image' => $image,
            'leader_id' => $leader->id,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => true,
        ]);

        // Assert - Redirect
        $response->assertRedirect(route('dashboard'));

        // Assert - Team created with image in database
        $team = Team::where('equ_name', 'Team with Image')->first();
        $this->assertNotNull($team);
        $this->assertNotNull($team->equ_image);
        $this->assertStringContainsString('teams/', $team->equ_image);

        // Assert - Image file exists in storage
        Storage::disk('public')->assertExists($team->equ_image);

        // Assert - All users attached (leader, teammate, and creator)
        $this->assertTrue($team->users()->whereIn('users.id', [
            $leader->id,
            $teammate->id,
            $creator->id,
        ])->count() === 3);
    }

    /**
     * Test team creation with creator joining the team.
     */
    public function test_create_team_with_creator_joining(): void
    {
        // Create users
        $leader = User::factory()->create();
        $teammate = User::factory()->create();
        $creator = User::factory()->create();

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with Creator',
            'image' => null,
            'leader_id' => $leader->id,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => true, // Creator joins the team
        ]);

        // Assert
        $response->assertRedirect(route('dashboard'));

        // Check that creator is in the team
        $team = Team::where('equ_name', 'Team with Creator')->first();
        $teamUserIds = $team->users()->pluck('users.id')->toArray();

        $this->assertContains($creator->id, $teamUserIds);
        $this->assertContains($leader->id, $teamUserIds);
        $this->assertContains($teammate->id, $teamUserIds);
    }

    /**
     * Test team creation fails without leader_id.
     */
    public function test_create_team_fails_without_leader_id(): void
    {
        $creator = User::factory()->create();

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Invalid Team',
            'image' => null,
            'leader_id' => null,
            'teammates' => [],
            'join_team' => false,
        ]);

        // Assert - Validation error
        $response->assertSessionHasErrors('leader_id');

        // Assert - Team not created
        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Invalid Team',
        ]);
    }

    /**
     * Test team creation fails with invalid leader_id.
     */
    public function test_create_team_fails_with_invalid_leader_id(): void
    {
        $creator = User::factory()->create();

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Invalid Team',
            'image' => null,
            'leader_id' => 99999, // Non-existent user
            'teammates' => [],
            'join_team' => false,
        ]);

        // Assert - Validation error
        $response->assertSessionHasErrors('leader_id');

        // Assert - Team not created
        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Invalid Team',
        ]);
    }

    /**
     * Test team creation fails with invalid teammate_id.
     */
    public function test_create_team_fails_with_invalid_teammate_id(): void
    {
        $creator = User::factory()->create();
        $leader = User::factory()->create();

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Invalid Team',
            'image' => null,
            'leader_id' => $leader->id,
            'teammates' => [
                ['id' => 99999], // Non-existent user
            ],
            'join_team' => false,
        ]);

        // Assert - Validation error
        $response->assertSessionHasErrors('teammates.0.id');

        // Assert - Team not created
        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Invalid Team',
        ]);
    }

    /**
     * Test team creation with large image fails.
     */
    public function test_create_team_fails_with_oversized_image(): void
    {
        Storage::fake('public');

        $leader = User::factory()->create();
        $creator = User::factory()->create();

        // Create a fake file larger than 2048KB
        $image = UploadedFile::fake()->create('large-image.txt', 3000);

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with Large Image',
            'image' => $image,
            'leader_id' => $leader->id,
            'teammates' => [],
            'join_team' => false,
        ]);

        // Assert - Validation error
        $response->assertSessionHasErrors('image');

        // Assert - Team not created
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

        $leader = User::factory()->create();
        $creator = User::factory()->create();

        // Create a fake non-image file
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Act
        $response = $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Team with PDF',
            'image' => $file,
            'leader_id' => $leader->id,
            'teammates' => [],
            'join_team' => false,
        ]);

        // Assert - Validation error
        $response->assertSessionHasErrors('image');

        // Assert - Team not created
        $this->assertDatabaseMissing('teams', [
            'equ_name' => 'Team with PDF',
        ]);
    }

    /**
     * Test has_participate table inserts correctly.
     */
    public function test_has_participate_table_inserts_correctly(): void
    {
        // Create users
        $leader = User::factory()->create();
        $teammate = User::factory()->create();
        $creator = User::factory()->create();

        // Act
        $this->actingAs($creator)->post(route('team.store'), [
            'name' => 'Table Test Team',
            'image' => null,
            'leader_id' => $leader->id,
            'teammates' => [
                ['id' => $teammate->id],
            ],
            'join_team' => true,
        ]);

        // Get the team
        $team = Team::where('equ_name', 'Table Test Team')->first();

        // Assert - Check has_participate records
        $this->assertDatabaseHas('has_participate', [
            'equ_id' => $team->equ_id,
            'id_users' => $leader->id,
        ]);

        $this->assertDatabaseHas('has_participate', [
            'equ_id' => $team->equ_id,
            'id_users' => $teammate->id,
        ]);

        $this->assertDatabaseHas('has_participate', [
            'equ_id' => $team->equ_id,
            'id_users' => $creator->id,
        ]);

        // Assert - Count of users in team
        $this->assertEquals(3, $team->users()->count());
    }
}
