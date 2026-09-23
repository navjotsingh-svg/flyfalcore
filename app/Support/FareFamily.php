<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FareFamily
{
    /**
     * @param  array<string, mixed>  $offer
     */
    public static function fingerprint(array $offer): string
    {
        $legs = $offer['legs'] ?? [];

        if ($legs === []) {
            return implode('|', [
                (string) ($offer['flight_number'] ?? ''),
                static::iso($offer['departure_at'] ?? null),
            ]);
        }

        return collect($legs)
            ->map(fn (array $leg) => ($leg['flight_number'] ?? '').'|'.static::iso($leg['departure_at'] ?? null))
            ->implode('~');
    }

    /**
     * One listing row per itinerary. Price is the cheapest matching cabin, or the cheapest fare.
     *
     * @param  Collection<int, array<string, mixed>>  $offers
     * @return Collection<int, array<string, mixed>>
     */
    public static function collapseForListing(Collection $offers, ?string $cabin = null): Collection
    {
        $grouped = $offers->groupBy(fn (array $offer) => $offer['fingerprint'] ?? static::fingerprint($offer));

        $collapsed = $grouped->map(function (Collection $group) use ($cabin) {
            $sorted = $group->sortBy('price')->values();
            $chosen = $cabin
                ? ($sorted->first(fn (array $offer) => ($offer['cabin_class'] ?? '') === $cabin) ?? $sorted->first())
                : $sorted->first();

            $chosen['from_price'] = (float) $sorted->min('price');
            $chosen['fare_count'] = $group->count();
            $chosen['cabins'] = $group->pluck('cabin_class')->unique()->values()->all();

            return $chosen;
        })->values();

        if ($cabin) {
            $matching = $collapsed->filter(fn (array $offer) => in_array($cabin, $offer['cabins'] ?? [], true))->values();
            if ($matching->isNotEmpty()) {
                return $matching;
            }
        }

        return $collapsed;
    }

    /**
     * Cheapest offer per cabin, in cabin order — the fare-options columns.
     *
     * @param  Collection<int, array<string, mixed>>|list<array<string, mixed>>  $offers
     * @return list<array<string, mixed>>
     */
    public static function cards(Collection|array $offers): array
    {
        $order = ['economy' => 0, 'premium_economy' => 1, 'business' => 2, 'first' => 3];

        return collect($offers)
            ->groupBy(fn (array $offer) => $offer['cabin_class'] ?? 'economy')
            ->map(fn (Collection $group) => $group->sortBy('price')->first())
            ->sortBy(fn (array $offer) => $order[$offer['cabin_class'] ?? 'economy'] ?? 9)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function termsFromOffer(array $raw, string $currency): array
    {
        $conditions = is_array($raw['conditions'] ?? null) ? $raw['conditions'] : [];

        return [
            'change' => static::condition($conditions['change_before_departure'] ?? null, 'change', $currency),
            'refund' => static::condition($conditions['refund_before_departure'] ?? null, 'refund', $currency),
            'hold' => static::hold($raw['payment_requirements'] ?? []),
            'bags' => static::bags($raw),
            'emissions' => static::emissions($raw),
            'fare_brand' => static::fareBrand($raw),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function termsForLocal(string $cabin, string $currency = 'INR'): array
    {
        $checked = $cabin !== 'economy';

        return [
            'change' => [
                'allowed' => true,
                'label' => 'Changeable before departure',
                'detail' => 'Airline change rules apply',
            ],
            'refund' => [
                'allowed' => false,
                'label' => 'Not refundable',
                'detail' => null,
            ],
            'hold' => [
                'allowed' => false,
                'label' => 'Pay to confirm this fare',
                'detail' => null,
            ],
            'bags' => [
                ['type' => 'carry_on', 'included' => true, 'label' => 'Includes carry-on bag'],
                ['type' => 'checked', 'included' => $checked, 'label' => $checked ? 'Includes checked bag' : 'Checked bag available at checkout'],
            ],
            'emissions' => null,
            'fare_brand' => match ($cabin) {
                'premium_economy' => 'Comfort',
                'business' => 'Premium',
                'first' => 'First',
                default => 'Standard',
            },
        ];
    }

    /**
     * @param  array<string, mixed>|null  $rule
     * @return array{allowed: bool, label: string, detail: ?string}
     */
    public static function condition(?array $rule, string $kind, string $currency): array
    {
        $verb = $kind === 'refund' ? 'Refundable' : 'Changeable';
        $denied = $kind === 'refund' ? 'Not refundable' : 'Not changeable';

        if (! is_array($rule) || ! array_key_exists('allowed', $rule)) {
            return [
                'allowed' => false,
                'label' => $kind === 'refund' ? 'Refund policy from the airline' : 'Change policy from the airline',
                'detail' => null,
            ];
        }

        if (! $rule['allowed']) {
            return ['allowed' => false, 'label' => $denied, 'detail' => null];
        }

        $penalty = $rule['penalty_amount'] ?? null;
        $penaltyCurrency = $rule['penalty_currency'] ?? $currency;

        if ($penalty !== null && $penalty !== '' && (float) $penalty > 0) {
            return [
                'allowed' => true,
                'label' => $verb.' ('.static::money($penalty, $penaltyCurrency).' fee)',
                'detail' => null,
            ];
        }

        return ['allowed' => true, 'label' => $verb, 'detail' => null];
    }

    /**
     * @param  array<string, mixed>  $requirements
     * @return array{allowed: bool, label: string, detail: ?string}
     */
    public static function hold(array $requirements): array
    {
        if (($requirements['requires_instant_payment'] ?? false) === true) {
            return ['allowed' => false, 'label' => 'Pay now to hold this fare', 'detail' => null];
        }

        $expires = $requirements['price_guarantee_expires_at'] ?? $requirements['payment_required_by'] ?? null;

        if (is_string($expires) && $expires !== '') {
            try {
                $at = Carbon::parse($expires);
                $hours = max(1, (int) now()->diffInHours($at, false));

                if ($hours > 0) {
                    $days = (int) floor($hours / 24);

                    return [
                        'allowed' => true,
                        'label' => $days >= 1
                            ? 'Hold price for '.$days.' day'.($days === 1 ? '' : 's')
                            : 'Hold price for '.$hours.' hour'.($hours === 1 ? '' : 's'),
                        'detail' => $at->format('D, j M · H:i'),
                    ];
                }
            } catch (\Throwable) {
            }
        }

        return ['allowed' => true, 'label' => 'Hold price & space', 'detail' => null];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array{type: string, included: bool, label: string}>
     */
    public static function bags(array $raw): array
    {
        $baggages = data_get($raw, 'slices.0.passengers.0.baggages');

        if (! is_array($baggages) || $baggages === []) {
            $baggages = data_get($raw, 'slices.0.segments.0.passengers.0.baggages');
        }

        $carry = 0;
        $checked = 0;

        foreach (is_array($baggages) ? $baggages : [] as $bag) {
            $qty = (int) ($bag['quantity'] ?? 0);
            $type = $bag['type'] ?? '';

            if ($type === 'carry_on') {
                $carry += $qty;
            }

            if ($type === 'checked') {
                $checked += $qty;
            }
        }

        return [
            [
                'type' => 'carry_on',
                'included' => $carry > 0,
                'label' => $carry > 0
                    ? 'Includes '.$carry.' carry-on bag'.($carry === 1 ? '' : 's')
                    : 'Carry-on bag not included',
            ],
            [
                'type' => 'checked',
                'included' => $checked > 0,
                'label' => $checked > 0
                    ? 'Includes '.$checked.' checked bag'.($checked === 1 ? '' : 's')
                    : 'Checked bag not included',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function emissions(array $raw): ?string
    {
        $kg = $raw['total_emissions_kg'] ?? data_get($raw, 'carbon_emissions.this_flight');

        if ($kg === null || $kg === '') {
            return null;
        }

        return number_format((float) $kg, 0).' kg CO₂';
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fareBrand(array $raw): string
    {
        $brand = data_get($raw, 'slices.0.fare_brand_name')
            ?? data_get($raw, 'slices.0.segments.0.passengers.0.fare_brand_name');

        if (is_string($brand) && trim($brand) !== '') {
            return trim($brand);
        }

        return match ($raw['cabin_class'] ?? 'economy') {
            'premium_economy' => 'Comfort',
            'business' => 'Premium',
            'first' => 'First',
            default => 'Basic',
        };
    }

    public static function money(float|int|string|null $amount, string $currency): string
    {
        $symbol = ['USD' => 'US$', 'GBP' => '£', 'EUR' => '€', 'INR' => '₹'][$currency] ?? $currency.' ';

        return $symbol.number_format((float) $amount, 2);
    }

    protected static function iso(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->toIso8601String();
            } catch (\Throwable) {
                return $value;
            }
        }

        return '';
    }
}
