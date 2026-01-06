<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_profile_returns_json_data(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/profile');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_api_profile_update_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patchJson('/profile', [
                'first_name' => 'API',
                'last_name' => 'User',
                'email' => 'api@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'API User')
            ->assertJsonPath('data.email', 'api@example.com');

        $user->refresh();
        $this->assertSame('API User', $user->name);
    }

    public function test_api_profile_delete_returns_no_content(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson('/profile', [
                'password' => 'password',
            ]);

        $response->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function test_api_profile_delete_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertUnprocessable(); // 422
        $this->assertNotNull($user->fresh());
    }
}
