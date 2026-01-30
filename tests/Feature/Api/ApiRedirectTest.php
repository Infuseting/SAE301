<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_404_does_not_redirect_to_dashboard()
    {
        // Try to access a non-existent club
        $response = $this->getJson('/api/clubs/999999');

        // It should be 404, not 302 redirect
        $response->assertStatus(404);

        // If it returns JSON, it shouldn't be an Inertia HTML page
        $response->assertHeader('Content-Type', 'application/json');
    }
}
