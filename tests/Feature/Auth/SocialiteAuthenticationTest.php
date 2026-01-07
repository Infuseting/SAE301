<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;
use Mockery;

class SocialiteAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_provider(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com', $response->getTargetUrl());
    }

    public function test_callback_creates_new_user(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('1234567890');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $abstractUser->shouldReceive('getNickname')->andReturn('testuser');
        $abstractUser->token = 'test-token';
        $abstractUser->refreshToken = 'test-refresh-token';
        $abstractUser->expiresIn = 3600;

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('setHttpClient')->andReturn($provider);
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('connected_accounts', [
            'provider' => 'google',
            'provider_id' => '1234567890',
        ]);
        $response->assertRedirect(route('home'));
    }

    public function test_callback_logs_in_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('1234567890');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $abstractUser->shouldReceive('getNickname')->andReturn('testuser');
        $abstractUser->token = 'test-token';
        $abstractUser->refreshToken = 'test-refresh-token';
        $abstractUser->expiresIn = 3600;

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('setHttpClient')->andReturn($provider);
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('connected_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '1234567890',
        ]);
        $response->assertRedirect(route('home'));
    }

    public function test_callback_links_account_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('987654321');
        $abstractUser->shouldReceive('getName')->andReturn('Linked User');
        $abstractUser->shouldReceive('getEmail')->andReturn('linked@example.com');
        $abstractUser->shouldReceive('getNickname')->andReturn('linkeduser');
        $abstractUser->token = 'linked-token';
        $abstractUser->refreshToken = 'linked-refresh-token';
        $abstractUser->expiresIn = 3600;

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('setHttpClient')->andReturn($provider);
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('connected_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '987654321',
        ]);
        $response->assertRedirect(route('profile.edit'));
    }
}
