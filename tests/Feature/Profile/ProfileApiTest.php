<?php

namespace Tests\Feature\Profile;

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
    public function test_api_profile_returns_json_birth_date(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/profile');

        $response->assertOk()
            ->assertJsonPath('data.birth_date', $user->birth_date);
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
                "birth_date" => "2000-01-01",
                "address" => "123 Main St",
                "phone" => "1234567890",
            ]);
        $response->dump();


        $response->assertOk()
            ->assertJsonPath('data.first_name', 'API')
            ->assertJsonPath('data.last_name', 'User')
            ->assertJsonPath('data.email', 'api@example.com')
            ->assertJsonPath('data.birth_date', '2000-01-01T00:00:00.000000Z')
            ->assertJsonPath('data.address', '123 Main St')
            ->assertJsonPath('data.phone', '1234567890');

        $user->refresh();
        $this->assertSame('API User', $user->first_name . ' ' . $user->last_name);
    }

    public function test_api_profile_delete_returns_no_content(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson('/profile', [
                'password' => 'password',
                'confirmation' => 'CONFIRMER',
            ]);

        $response->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function test_api_profile_delete_fails_with_wrong_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson('/profile', [
                'password' => 'password',
                'confirmation' => 'WRONG',
            ]);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors('confirmation');
        $this->assertNotNull($user->fresh());
    }
}
