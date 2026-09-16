<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_public_welcome_page_introduces_civiclear_and_links_to_staff_login(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Clear roads.')
            ->assertSee('Connected communities.')
            ->assertSee('DILG Memorandum Circular No. 2024-053')
            ->assertSee('Citizen Mobile App')
            ->assertSee(route('login'), false);
    }
}
