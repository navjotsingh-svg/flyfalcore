<?php

namespace App\Support;

class AirlineCopy
{
    public static function publicMessage(string $message): string
    {
        $replacements = [
            'TBO' => 'the airline',
            'tbo' => 'the airline',
            'Tektravels' => 'the airline',
        ];

        return str_ireplace(array_keys($replacements), array_values($replacements), $message);
    }

    public static function airlineName(?string $name, string $fallback = 'Airline'): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $fallback;
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
