<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_receives_profile_completion_status()
    {
        $user = User::factory()->create([
            'birth_date' => null, // Incomplete
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Welcome') // Dashboard renders Welcome currently in web.php
                    ->has(
                        'auth.user',
                        fn(Assert $json) => $json
                            ->where('has_completed_profile', false)
                            ->etc()
                    )
            );
    }

    public function test_completed_profile_status()
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
            'address' => '123 Main St',
            'phone' => '1234567890',
            'license_number' => 'LIC-123',
            'medical_certificate_code' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->has(
                        'auth.user',
                        fn(Assert $json) => $json
                            ->where('has_completed_profile', true)
                            ->etc()
                    )
            );
    }

    public function test_auth_user_sees_modal_on_welcome_page()
    {
        $user = User::factory()->create([
            'birth_date' => null, // Incomplete
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Welcome')
                    ->has(
                        'auth.user',
                        fn(Assert $json) => $json
                            ->where('has_completed_profile', false)
                            ->etc()
                    )
            );
    }
}
