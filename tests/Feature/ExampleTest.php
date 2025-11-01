<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Ensure the home page redirects unauthenticated users to the login page.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        // Guests should be redirected (302) to the login route
        $response->assertStatus(302)
                 ->assertRedirect('/login');
    }
}