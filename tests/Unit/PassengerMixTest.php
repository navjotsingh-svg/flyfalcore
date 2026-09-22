<?php

namespace Tests\Unit;

use App\Support\PassengerMix;
use Tests\TestCase;

class PassengerMixTest extends TestCase
{
    public function test_it_classifies_ages_for_duffel_fare_types(): void
    {
        $travel = '2026-09-22';

        $this->assertSame('infant_without_seat', PassengerMix::typeForAge(0));
        $this->assertSame('infant_without_seat', PassengerMix::typeForAge(1));
        $this->assertSame('child', PassengerMix::typeForAge(2));
        $this->assertSame('child', PassengerMix::typeForAge(11));
        $this->assertSame('adult', PassengerMix::typeForAge(12));

        $this->assertTrue(PassengerMix::dobMatchesType('infant_without_seat', '2026-03-15', $travel));
        $this->assertFalse(PassengerMix::dobMatchesType('adult', '2026-03-15', $travel));
        $this->assertTrue(PassengerMix::dobMatchesType('adult', '1988-01-15', $travel));
    }

    public function test_year_dropdown_matches_fare_type(): void
    {
        $range = PassengerMix::yearRange('adult', '2026-09-22', '2026-09-22');
        $this->assertSame(1900, $range['min']);
        $this->assertSame(2014, $range['max']);

        $child = PassengerMix::yearRange('child', '2026-09-22', '2026-09-22');
        $this->assertSame(2014, $child['min']);
        $this->assertSame(2024, $child['max']);

        $infant = PassengerMix::yearRange('infant_without_seat', '2026-09-22', '2026-09-22');
        $this->assertSame(2024, $infant['min']);
        $this->assertSame(2026, $infant['max']);
    }

    public function test_infants_cannot_outnumber_adults(): void
    {
        $mix = PassengerMix::fromArray([
            'adults' => 1,
            'children' => 0,
            'infants' => 3,
        ]);

        $this->assertSame(1, $mix->adults);
        $this->assertSame(1, $mix->infants);
        $this->assertSame(['adult', 'infant_without_seat'], $mix->types());
    }
}
