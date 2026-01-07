<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'CONFIRMER',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
    }

    public function test_correct_confirmation_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'WRONG',
        ]);

        $response->assertSessionHasErrors('confirmation');
        $this->assertNotNull(User::find($user->id));
    }
}
