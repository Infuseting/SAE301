<?php

namespace Tests\Feature;

use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ErrorPageTest extends TestCase
{
    public function test_404_page_is_rendered()
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);

        // Assert that it renders the Inertia Error component
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
        );
    }
}
