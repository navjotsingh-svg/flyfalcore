<?php

namespace Tests\Unit;

use App\Support\AncillaryCatalog;
use Tests\TestCase;

class AncillaryCatalogTest extends TestCase
{
    public function test_local_catalog_prices_bags_and_keeps_window_seats_open(): void
    {
        $flight = new \App\Models\Flight([
            'cabin_class' => 'business',
        ]);
        $flight->id = 1;

        $catalog = AncillaryCatalog::forLocal($flight, [
            ['type' => 'adult', 'label' => 'Adult'],
            ['type' => 'infant_without_seat', 'label' => 'Infant'],
        ], 'INR');

        $this->assertSame('Business', $catalog['cabin_label']);
        $this->assertArrayHasKey(0, $catalog['bags']);
        $this->assertArrayNotHasKey(1, $catalog['bags']);
        $this->assertTrue(collect($catalog['seats'])->contains(fn ($seat) => $seat['designator'] === '12A' && $seat['available']));

        $resolved = AncillaryCatalog::resolve($catalog, [
            'bags' => [1],
            'seats' => ['12A'],
        ], [
            ['type' => 'adult'],
            ['type' => 'infant_without_seat'],
        ]);

        $this->assertSame(1800.0, $resolved['total']);
        $this->assertSame('12A', $resolved['seats'][0]);
        $this->assertSame(1, $resolved['bags'][0]);
    }
}
