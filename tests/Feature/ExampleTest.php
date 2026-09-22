<?php

namespace Tests\Feature;

use Database\Seeders\AirportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(AirportSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('info@flyfalcore.com')
            ->assertSee('+1 818 666 0913')
            ->assertSee('+91 98889 49774')
            ->assertSee('The Core of')
            ->assertSee('Connecting People. Powering Travel. Moving the World.')
            ->assertSee('Partner With Falcore')
            ->assertDontSee('Travelera')
            ->assertDontSee('travelera.us');
    }

    public function test_official_pages_render(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('At the Core of')
            ->assertSee('Modern Travel');

        $this->get('/services')
            ->assertOk()
            ->assertSee('Air Travel Services');

        $this->get('/corporate-travel')
            ->assertOk()
            ->assertSee('Business Travel');

        $this->get('/partnerships')
            ->assertOk()
            ->assertSee('Built for Travel');

        $this->get('/travelers')
            ->assertOk()
            ->assertSee('One Travel Ecosystem')
            ->assertSee('Global Travel Services')
            ->assertDontSee('Travelera')
            ->assertDontSee('TRAVELERA')
            ->assertDontSee('travelera.us');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('info@flyfalcore.com')
            ->assertSee('+1 818 666 0913')
            ->assertSee('+91 98889 49774');

        $this->get('/signup')
            ->assertOk()
            ->assertSee('Create your Falcore account')
            ->assertSee('Saved passengers')
            ->assertSee('Send verification code');
    }
}
