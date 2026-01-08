<?php

namespace Tests\Feature\Auth;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_connected_accounts()
    {
        $user = User::factory()->create();

        $account = ConnectedAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456789',
            'token' => 'test-token',
        ]);

        $this->assertTrue($user->connectedAccounts->contains($account));
        $this->assertEquals($user->id, $account->user->id);
    }

    public function test_connected_account_can_store_and_retrieve_tokens()
    {
        $user = User::factory()->create();

        ConnectedAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '987654321',
            'token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
        ]);

        $account = $user->connectedAccounts()->first();

        $this->assertEquals('github', $account->provider);
        $this->assertEquals('access-token', $account->token);
        $this->assertNotNull($account->expires_at);
    }

    public function test_user_can_have_multiple_providers()
    {
        $user = User::factory()->create();

        ConnectedAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-id',
            'token' => 't1',
        ]);

        ConnectedAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => 'github-id',
            'token' => 't2',
        ]);

        $this->assertCount(2, $user->connectedAccounts);
    }
}
