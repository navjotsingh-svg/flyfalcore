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

    public static function airlineName(?string $name, string $fallback = 'Airline'): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $fallback;
        }

        if (stripos($name, 'duffel') !== false) {
            return 'Falcore Airways';
        }

        return $name;
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

    /**
     * Live-search cabin classes, matching airline offer classes.
     *
     * @return list<array{value: string, label: string, hint: string, icon: string}>
     */
    public static function cabinOptions(bool $includeAny = false): array
    {
        $options = [];

        if ($includeAny) {
            $options[] = [
                'value' => '',
                'label' => 'Any class',
                'hint' => 'Show every cabin',
                'icon' => 'fa-layer-group',
            ];
        }

        return array_merge($options, [
            ['value' => 'economy', 'label' => 'Economy', 'hint' => 'Standard seat', 'icon' => 'fa-chair'],
            ['value' => 'premium_economy', 'label' => 'Premium economy', 'hint' => 'Extra space', 'icon' => 'fa-couch'],
            ['value' => 'business', 'label' => 'Business', 'hint' => 'Priority cabin', 'icon' => 'fa-briefcase'],
            ['value' => 'first', 'label' => 'First', 'hint' => 'Finest cabin', 'icon' => 'fa-crown'],
        ]);
    }
}
