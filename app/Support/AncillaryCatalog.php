<?php

namespace App\Support;

use App\Models\Flight;
use Illuminate\Validation\ValidationException;

class AncillaryCatalog
{
    /**
     * @param  list<array<string, mixed>>  $slots
     * @return array<string, mixed>
     */
    public static function forLocal(Flight $flight, array $slots, string $currency = 'INR'): array
    {
        $cabin = $flight->cabin_class ?: 'economy';

        return [
            'cabin_class' => $cabin,
            'cabin_label' => AirlineCopy::cabinLabel($cabin),
            'currency' => $currency,
            'bags' => self::bagsForSlots($slots, $currency, []),
            'seats' => self::localSeatList('flight-'.$flight->id, $currency, true),
            'letters' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'aisle_after' => 'C',
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  list<array<string, mixed>>  $seatMaps
     * @param  list<array<string, mixed>>  $slots
     * @return array<string, mixed>
     */
    public static function forLiveOffer(array $offer, array $seatMaps, array $slots): array
    {
        $currency = $offer['currency'] ?? 'USD';
        $cabin = $offer['cabin_class'] ?? 'economy';
        $passengers = $offer['passengers'] ?? [];
        $services = $offer['available_services'] ?? data_get($offer, 'raw.available_services', []);

        $seats = self::seatsFromMaps(is_array($seatMaps) ? $seatMaps : [], $passengers);
        if ($seats === []) {
            $seats = self::localSeatList((string) ($offer['id'] ?? 'offer'), $currency, false);
        }

        return [
            'cabin_class' => $cabin,
            'cabin_label' => AirlineCopy::cabinLabel($cabin),
            'currency' => $currency,
            'bags' => self::bagsForSlots($slots, $currency, is_array($services) ? $services : [], $passengers),
            'seats' => $seats,
            'letters' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'aisle_after' => 'C',
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $slots
     * @return array<string, mixed>
     */
    public static function resolve(array $catalog, array $input, array $slots): array
    {
        $bagsInput = $input['bags'] ?? [];
        $seatsInput = $input['seats'] ?? [];
        $seatIndex = collect($catalog['seats'] ?? [])->keyBy('designator');

        $lines = [];
        $services = [];
        $seats = [];
        $bags = [];
        $total = 0.0;
        $airlineTotal = 0.0;
        $usedSeats = [];

        foreach ($slots as $index => $slot) {
            $type = PassengerMix::normaliseType($slot['type'] ?? 'adult');
            if ($type === PassengerMix::INFANT) {
                continue;
            }

            $bagOption = $catalog['bags'][$index] ?? null;
            $qty = max(0, (int) ($bagsInput[$index] ?? 0));
            if ($bagOption) {
                $qty = min($qty, (int) ($bagOption['max'] ?? 2));
            } else {
                $qty = 0;
            }

            if ($qty > 0 && $bagOption) {
                $amount = round($qty * (float) $bagOption['amount'], 2);
                $total += $amount;
                $bags[$index] = $qty;
                $lines[] = [
                    'label' => $bagOption['label'].' × '.$qty,
                    'amount' => $amount,
                    'passenger_index' => $index,
                    'kind' => 'bag',
                ];

                if (filled($bagOption['service_id'] ?? null)) {
                    $airlineTotal += $amount;
                    $services[] = [
                        'id' => $bagOption['service_id'],
                        'quantity' => $qty,
                    ];
                }
            }

            $designator = strtoupper(trim((string) ($seatsInput[$index] ?? '')));
            if ($designator === '') {
                continue;
            }

            $seat = $seatIndex->get($designator);
            if (! is_array($seat) || empty($seat['available'])) {
                throw ValidationException::withMessages([
                    "extras.seats.$index" => 'That seat is not available. Choose another seat.',
                ]);
            }

            $allowed = $seat['passenger_indexes'] ?? [];
            if ($allowed !== [] && ! in_array($index, $allowed, true)) {
                throw ValidationException::withMessages([
                    "extras.seats.$index" => 'That seat is not available for this traveller.',
                ]);
            }

            if (isset($usedSeats[$designator])) {
                throw ValidationException::withMessages([
                    "extras.seats.$index" => 'Each traveller needs a different seat.',
                ]);
            }

            $usedSeats[$designator] = $index;
            $seatAmount = round((float) ($seat['amount'] ?? 0), 2);
            $total += $seatAmount;
            $seats[$index] = $designator;
            $lines[] = [
                'label' => 'Seat '.$designator,
                'amount' => $seatAmount,
                'passenger_index' => $index,
                'kind' => 'seat',
            ];

            if (filled($seat['service_id'] ?? null)) {
                $airlineTotal += $seatAmount;
                $services[] = [
                    'id' => $seat['service_id'],
                    'quantity' => 1,
                ];
            }
        }

        return [
            'total' => round($total, 2),
            'airline_total' => round($airlineTotal, 2),
            'services' => $services,
            'seats' => $seats,
            'bags' => $bags,
            'lines' => $lines,
            'cabin_class' => $catalog['cabin_class'] ?? 'economy',
            'cabin_label' => $catalog['cabin_label'] ?? AirlineCopy::cabinLabel($catalog['cabin_class'] ?? null),
        ];
    }

    public static function bagPrice(string $currency): float
    {
        return match (strtoupper($currency)) {
            'INR' => 1500.00,
            'GBP' => 25.00,
            'EUR' => 28.00,
            'AED' => 90.00,
            default => 35.00,
        };
    }

    public static function seatPrice(string $currency): float
    {
        return match (strtoupper($currency)) {
            'INR' => 300.00,
            'GBP' => 8.00,
            'EUR' => 9.00,
            'AED' => 25.00,
            default => 12.00,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $slots
     * @param  list<array<string, mixed>>  $services
     * @param  list<array<string, mixed>>  $offerPassengers
     * @return array<int, array<string, mixed>>
     */
    protected static function bagsForSlots(array $slots, string $currency, array $services, array $offerPassengers = []): array
    {
        $bags = [];

        foreach ($slots as $index => $slot) {
            $type = PassengerMix::normaliseType($slot['type'] ?? 'adult');
            if ($type === PassengerMix::INFANT) {
                continue;
            }

            $passengerId = $offerPassengers[$index]['id'] ?? null;
            $match = collect($services)->first(function ($service) use ($passengerId) {
                if (($service['type'] ?? '') !== 'baggage') {
                    return false;
                }

                $ids = $service['passenger_ids'] ?? [];

                return $passengerId === null || $ids === [] || in_array($passengerId, $ids, true);
            });

            if (is_array($match)) {
                $weight = data_get($match, 'metadata.maximum_weight_kg', 23);
                $bags[$index] = [
                    'service_id' => $match['id'] ?? null,
                    'label' => $weight.' kg checked bag',
                    'amount' => (float) ($match['total_amount'] ?? self::bagPrice($currency)),
                    'max' => max(1, min(2, (int) ($match['maximum_quantity'] ?? 1))),
                ];

                continue;
            }

            $bags[$index] = [
                'service_id' => null,
                'label' => '23 kg extra checked bag',
                'amount' => self::bagPrice($currency),
                'max' => 2,
            ];
        }

        return $bags;
    }

    /**
     * @param  list<array<string, mixed>>  $maps
     * @param  list<array<string, mixed>>  $offerPassengers
     * @return list<array<string, mixed>>
     */
    protected static function seatsFromMaps(array $maps, array $offerPassengers): array
    {
        $seats = [];

        foreach ($maps as $map) {
            foreach ($map['cabins'] ?? [] as $cabin) {
                foreach ($cabin['rows'] ?? [] as $row) {
                    foreach ($row['sections'] ?? [] as $section) {
                        foreach ($section['elements'] ?? [] as $element) {
                            if (($element['type'] ?? '') !== 'seat') {
                                continue;
                            }

                            $designator = strtoupper((string) ($element['designator'] ?? ''));
                            if ($designator === '') {
                                continue;
                            }

                            $services = $element['available_services'] ?? [];
                            $available = is_array($services) && $services !== [];
                            $amount = 0.0;
                            $serviceId = null;
                            $passengerIndexes = [];

                            foreach ($services as $service) {
                                $amount = (float) ($service['total_amount'] ?? 0);
                                $serviceId = $service['id'] ?? $serviceId;
                                $passengerId = $service['passenger_id'] ?? null;
                                foreach ($offerPassengers as $index => $passenger) {
                                    if ($passengerId && ($passenger['id'] ?? null) === $passengerId) {
                                        $passengerIndexes[] = $index;
                                    }
                                }
                            }

                            $seats[] = [
                                'designator' => $designator,
                                'row' => (int) preg_replace('/\D+/', '', $designator),
                                'letter' => strtoupper((string) preg_replace('/\d+/', '', $designator)),
                                'amount' => $amount,
                                'service_id' => $available ? $serviceId : null,
                                'available' => $available,
                                'passenger_indexes' => array_values(array_unique($passengerIndexes)),
                            ];
                        }
                    }
                }
            }
        }

        return $seats;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function localSeatList(string $seed, string $currency, bool $charge): array
    {
        $amount = $charge ? self::seatPrice($currency) : 0.0;
        $reserved = ['12A', '12F', '14A', '14F', '15C'];
        $seats = [];

        for ($row = 8; $row <= 20; $row++) {
            if ($row === 13) {
                continue;
            }

            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $letter) {
                $designator = $row.$letter;
                $taken = ! in_array($designator, $reserved, true)
                    && (crc32($seed.$designator) % 7 === 0);

                $seats[] = [
                    'designator' => $designator,
                    'row' => $row,
                    'letter' => $letter,
                    'amount' => $taken ? 0.0 : $amount,
                    'service_id' => null,
                    'available' => ! $taken,
                    'passenger_indexes' => [],
                ];
            }
        }

        return $seats;
    }
}
