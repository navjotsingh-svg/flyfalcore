<?php

namespace Tests\Feature;

use Database\Seeders\AirportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirportSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_queries_return_no_suggestions(): void
    {
        $this->getJson('/airports/suggest?q=l')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_airport_search_returns_matching_suggestions(): void
    {
        $this->seed(AirportSeeder::class);

        $this->getJson('/airports/suggest?q=lon')
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'LHR',
                'city' => 'London',
            ])
            ->assertJsonFragment([
                'code' => 'LGW',
            ]);
    }

    public function test_home_search_uses_text_fields_instead_of_airport_dropdowns(): void
    {
        $this->seed(AirportSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('City or airport')
            ->assertDontSee('<select name="from"', false)
            ->assertDontSee('<select name="to"', false);
    }
}
