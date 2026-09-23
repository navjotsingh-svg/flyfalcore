<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class FlightCard
{
    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function fromOffer(array $offer, PassengerMix $mix, array $filters = []): array
    {
        $legs = $offer['legs'] ?? [];
        if ($legs === []) {
            $legs = [[
                'label' => 'Outbound',
                'airline' => $offer['airline'] ?? '',
                'airline_code' => $offer['airline_code'] ?? '',
                'flight_number' => $offer['flight_number'] ?? '',
                'origin_code' => $offer['origin_code'] ?? '',
                'origin_city' => $offer['origin_city'] ?? '',
                'destination_code' => $offer['destination_code'] ?? '',
                'destination_city' => $offer['destination_city'] ?? '',
                'departure_at' => $offer['departure_at'],
                'arrival_at' => $offer['arrival_at'],
                'formatted_duration' => $offer['formatted_duration'] ?? '',
                'stops' => $offer['stops'] ?? 0,
                'via' => $offer['via'] ?? [],
            ]];
        }

        $bookQuery = $mix->query();
        if (! empty($offer['return_flight_id'])) {
            $bookQuery['return_flight'] = $offer['return_flight_id'];
        }

        $currency = $offer['currency'] ?? 'USD';
        $fromPrice = (float) ($offer['from_price'] ?? $offer['price']);
        $formattedLegs = array_map(fn (array $leg) => static::leg($leg), $legs);
        $roundTrip = count($formattedLegs) > 1;
        $selectQuery = $bookQuery;
        $selectUrl = ($offer['source'] ?? '') === 'duffel'
            ? route('offers.show', $offer['id'])
            : route('flights.fares', array_merge(['flight' => $offer['id']], $selectQuery));

        return [
            'id' => (string) $offer['id'],
            'source' => ($offer['source'] ?? 'local') === 'duffel' ? 'live' : ($offer['source'] ?? 'local'),
            'airline' => $offer['airline'] ?? ($formattedLegs[0]['airline'] ?? 'Airline'),
            'airline_code' => $offer['airline_code'] ?? ($formattedLegs[0]['airline_code'] ?? ''),
            'cabin' => $offer['cabin_label'] ?? AirlineCopy::cabinLabel($offer['cabin_class'] ?? 'economy'),
            'cabin_class' => $offer['cabin_class'] ?? 'economy',
            'price' => (float) $offer['price'],
            'from_price' => $fromPrice,
            'price_label' => FareFamily::money($fromPrice, $currency),
            'fare_hint' => ($offer['fare_count'] ?? 1) > 1 ? 'From' : 'Total',
            'duration_minutes' => (int) ($offer['duration_minutes'] ?? ($formattedLegs[0]['duration_minutes'] ?? 0)),
            'stops' => (int) ($offer['stops'] ?? ($formattedLegs[0]['stops'] ?? 0)),
            'select_url' => $selectUrl,
            'book_url' => $selectUrl,
            'available_seats' => $offer['available_seats'] ?? null,
            'is_round_trip' => $roundTrip,
            'fare_count' => (int) ($offer['fare_count'] ?? 1),
            'legs' => $formattedLegs,
            'outbound' => $formattedLegs[0],
            'inbound' => $formattedLegs[1] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $leg
     * @return array<string, mixed>
     */
    public static function leg(array $leg): array
    {
        $depart = static::time($leg['departure_at'] ?? now());
        $arrive = static::time($leg['arrival_at'] ?? now());
        $via = $leg['via'] ?? [];
        $stops = (int) ($leg['stops'] ?? 0);
        $stopLabel = $stops === 0 ? 'Non stop' : $stops.' stop'.($stops > 1 ? 's' : '');
        if ($via !== []) {
            $stopLabel .= ' · via '.implode(', ', $via);
        }
        $code = $leg['airline_code'] ?? '';
        $initials = $code !== ''
            ? $code
            : collect(explode(' ', (string) ($leg['airline'] ?? 'F')))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('');

        return [
            'key' => ($leg['flight_number'] ?? '').'|'.$depart->toIso8601String(),
            'label' => $leg['label'] ?? 'Outbound',
            'airline' => $leg['airline'] ?? '',
            'airline_code' => $code,
            'initials' => $initials ?: 'F',
            'flight_number' => $leg['flight_number'] ?? '',
            'origin_code' => $leg['origin_code'] ?? '',
            'origin_city' => $leg['origin_city'] ?? '',
            'destination_code' => $leg['destination_code'] ?? '',
            'destination_city' => $leg['destination_city'] ?? '',
            'depart_time' => $depart->format('H:i'),
            'depart_date' => $depart->format('D, j M'),
            'arrive_time' => $arrive->format('H:i'),
            'arrive_date' => $arrive->format('D, j M'),
            'plus_days' => $depart->copy()->startOfDay()->diffInDays($arrive->copy()->startOfDay()),
            'duration' => $leg['formatted_duration'] ?? '',
            'duration_minutes' => (int) ($leg['duration_minutes'] ?? 0),
            'stops' => $stops,
            'stop_label' => $stopLabel,
        ];
    }

    protected static function time(mixed $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }
}
