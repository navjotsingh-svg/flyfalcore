<?php

namespace App\Support;

class AirlineCopy
{
    public static function publicMessage(string $message): string
    {
        $replacements = [
            'live Duffel fares' => 'live airline fares',
            'Duffel live fare' => 'Live airline fare',
            'Duffel test token' => 'airline search credentials',
            'Duffel order' => 'airline order',
            'Duffel offer' => 'airline offer',
            'Duffel passenger' => 'passenger',
            'reach Duffel' => 'reach live airline search',
            'Duffel is not configured. Add DUFFEL_ACCESS_TOKEN to your .env file.' => 'Live airline search is not configured.',
            'Duffel could not issue this ticket.' => 'The airline could not issue this ticket.',
            'Duffel request failed.' => 'Airline request failed.',
            'Duffel' => 'the airline',
            'duffel' => 'the airline',
        ];

        return str_ireplace(array_keys($replacements), array_values($replacements), $message);
    }

    public static function cabinLabel(?string $cabin): string
    {
        return match ($cabin) {
            'premium_economy' => 'Premium economy',
            'business' => 'Business',
            'first' => 'First',
            default => 'Economy',
        };
    }
}
