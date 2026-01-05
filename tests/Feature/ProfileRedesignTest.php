<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_can_be_updated_with_new_fields()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $photo = UploadedFile::fake()->create('photo.jpg', 100);

        $response = $this->actingAs($user)
            ->post('/profile', [ // Use POST but with _method PATCH logic in controller interaction or just follow route definition
                '_method' => 'PATCH',
                'name' => 'Updated Name',
                'email' => 'test@example.com',
                'description' => 'My new bio',
                'license_number' => 'TEST-LIC-123',
                'is_public' => true,
                'photo' => $photo,
            ]);

        if (session('errors')) {
            dump(session('errors')->all());
        }
        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile/edit');

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('My new bio', $user->description);
        $this->assertTrue((bool) $user->is_public);
        $this->assertNotNull($user->profile_photo_path);

        // Use standard assertion to avoid lint error on interface
        $this->assertTrue(Storage::disk('public')->exists($user->profile_photo_path));
    }

    public function test_public_profile_is_visible()
    {
        $user = User::factory()->create([
            'is_public' => true,
            'name' => 'John Public',
            'description' => 'Hello World',
        ]);

        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)
            ->get("/profile/{$user->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Profile/Show')
                ->where('user.name', 'John Public')
                ->where('user.description', 'Hello World')
        );
    }

    public function test_private_profile_shows_restricted_view()
    {
        $user = User::factory()->create([
            'is_public' => false,
            'name' => 'Jane Private',
        ]);

        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)
            ->get("/profile/{$user->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Profile/Show')
                ->where('user.is_public', false)
                ->where('user.name', 'Jane Private')
                ->missing('user.description')
        );
    }
}
