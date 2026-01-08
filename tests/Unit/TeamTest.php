<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test team can be created with mass assignment.
     */
    public function test_team_can_be_created(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('teams', [
            'equ_name' => 'Test Team',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test team has correct primary key.
     */
    public function test_team_has_correct_primary_key(): void
    {
        $team = new Team();
        $this->assertEquals('equ_id', $team->getKeyName());
    }

    /**
     * Test team uses correct table name.
     */
    public function test_team_uses_correct_table(): void
    {
        $team = new Team();
        $this->assertEquals('teams', $team->getTable());
    }

    /**
     * Test team fillable attributes.
     */
    public function test_team_fillable_attributes(): void
    {
        $team = new Team();
        $fillable = $team->getFillable();

        $this->assertContains('equ_name', $fillable);
        $this->assertContains('equ_image', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    /**
     * Test team leader relationship.
     */
    public function test_team_leader_relationship(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $team->leader);
        $this->assertEquals($user->id, $team->leader->id);
    }

    /**
     * Test team users relationship (participants).
     */
    public function test_team_users_relationship(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'user_id' => $user1->id,
        ]);

        $team->users()->attach([$user1->id, $user2->id, $user3->id]);

        $this->assertEquals(3, $team->users()->count());
        $this->assertTrue($team->users()->where('users.id', $user1->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $user2->id)->exists());
        $this->assertTrue($team->users()->where('users.id', $user3->id)->exists());
    }

    /**
     * Test team can have image.
     */
    public function test_team_can_have_image(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'equ_image' => 'teams/test-image.jpg',
            'user_id' => $user->id,
        ]);

        $this->assertEquals('teams/test-image.jpg', $team->equ_image);
    }

    /**
     * Test team image is nullable.
     */
    public function test_team_image_is_nullable(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'equ_image' => null,
            'user_id' => $user->id,
        ]);

        $this->assertNull($team->equ_image);
    }

    /**
     * Test team leader can be different from participants.
     */
    public function test_team_leader_can_be_separate_from_participants(): void
    {
        $leader = User::factory()->create();
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'user_id' => $leader->id,
        ]);

        // Only attach participants, not the leader
        $team->users()->attach([$participant1->id, $participant2->id]);

        // Leader is set
        $this->assertEquals($leader->id, $team->user_id);
        $this->assertEquals($leader->id, $team->leader->id);

        // Leader is NOT in participants
        $this->assertEquals(2, $team->users()->count());
        $this->assertFalse($team->users()->where('users.id', $leader->id)->exists());
    }

    /**
     * Test team timestamps are managed.
     */
    public function test_team_has_timestamps(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'equ_name' => 'Test Team',
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($team->created_at);
        $this->assertNotNull($team->updated_at);
    }
}
