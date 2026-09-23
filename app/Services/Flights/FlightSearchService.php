<?php

namespace App\Services\Flights;

use App\Exceptions\DuffelException;
use App\Models\Airport;
use App\Models\Flight;
use App\Services\Duffel\DuffelClient;
use App\Support\AirlineCopy;
use App\Support\PassengerMix;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FlightSearchService
{
    public function __construct(protected DuffelClient $duffel) {}

    public static function returnDate(array $filters): ?string
    {
        foreach (['return_date', 'return'] as $key) {
            if (filled($filters[$key] ?? null)) {
                return (string) $filters[$key];
            }
        }

        return null;
    }

    public function search(array $filters): array
    {
        $origin = $this->airportCode($filters['from'] ?? null);
        $destination = $this->airportCode($filters['to'] ?? null);
        $date = $filters['date'] ?? null;
        $returnDate = static::returnDate($filters);
        $cabin = $this->cabin($filters['cabin'] ?? null);
        $mix = PassengerMix::fromArray($filters);

        if ($returnDate && $date && $returnDate < $date) {
            $returnDate = null;
        }

        if ($this->duffel->configured() && $origin && $destination && $date) {
            try {
                return [
                    'source' => 'duffel',
                    'offers' => $this->searchDuffel($origin, $destination, $date, $returnDate, $cabin, $mix, $filters['sort'] ?? 'price'),
                    'message' => null,
                ];
            } catch (DuffelException $exception) {
                Log::warning('Duffel search failed, falling back to local flights', [
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors,
                ]);

                return [
                    'source' => 'local',
                    'offers' => $this->searchLocal($filters, $mix->seatedCount()),
                    'message' => 'Live airline search is unavailable right now. Showing Falcore sample flights instead.',
                ];
            }
        }

        return [
            'source' => 'local',
            'offers' => $this->searchLocal($filters, $mix->seatedCount()),
            'message' => $this->duffel->configured()
                ? 'Choose origin, destination, and date to search live airline fares.'
                : 'Live airline search is not configured. Showing sample flights for now.',
        ];
    }

    protected function searchDuffel(
        string $origin,
        string $destination,
        string $date,
        ?string $returnDate,
        ?string $cabin,
        PassengerMix $mix,
        string $sort,
    ): Collection {
        $slices = [[
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $date,
        ]];

        if (filled($returnDate)) {
            $slices[] = [
                'origin' => $destination,
                'destination' => $origin,
                'departure_date' => $returnDate,
            ];
        }

        $payload = [
            'slices' => $slices,
            'passengers' => $mix->duffelPassengers(),
            'max_connections' => 1,
        ];

        if ($cabin) {
            $payload['cabin_class'] = $cabin;
        }

        $request = $this->duffel->createOfferRequest($payload);
        $offerRequestId = data_get($request, 'data.id');
        $duffelSort = $sort === 'duration' ? 'total_duration' : 'total_amount';
        $offers = $this->duffel->listOffers((string) $offerRequestId, 40, $duffelSort);

        return collect($offers)
            ->map(fn (array $offer) => $this->presentDuffelOffer($offer, $offerRequestId))
            ->filter()
            ->values();
    }

    public function presentDuffelOffer(array $offer, ?string $offerRequestId = null): array
    {
        $slices = $offer['slices'] ?? [];
        $legs = collect($slices)
            ->values()
            ->map(fn (array $slice, int $index) => $this->presentDuffelSlice($slice, $index === 0 ? 'Outbound' : 'Return'))
            ->all();
        $firstLeg = $legs[0] ?? $this->presentDuffelSlice($slices[0] ?? [], 'Outbound');
        $owner = $offer['owner'] ?? [];
        $currency = $offer['total_currency'] ?? 'USD';
        $cabin = $offer['cabin_class'] ?? $firstLeg['cabin_class'] ?? 'economy';
        $presented = array_merge($firstLeg, [
            'source' => 'duffel',
            'id' => $offer['id'],
            'offer_request_id' => $offerRequestId ?? $offer['offer_request_id'] ?? null,
            'airline' => AirlineCopy::airlineName($owner['name'] ?? ($firstLeg['airline'] ?? null)),
            'airline_code' => $owner['iata_code'] ?? ($firstLeg['airline_code'] ?? ''),
            'cabin_class' => $cabin,
            'cabin_label' => AirlineCopy::cabinLabel($cabin),
            'available_services' => $offer['available_services'] ?? [],
            'price' => (float) ($offer['total_amount'] ?? 0),
            'currency' => $currency,
            'passengers' => $offer['passengers'] ?? [],
            'expires_at' => isset($offer['expires_at']) ? Carbon::parse($offer['expires_at']) : null,
            'slices' => $slices,
            'legs' => $legs,
            'is_round_trip' => count($legs) > 1,
            'terms' => \App\Support\FareFamily::termsFromOffer($offer, $currency),
            'raw' => $offer,
        ]);
        $presented['fingerprint'] = \App\Support\FareFamily::fingerprint($presented);

        return $presented;
    }

    /**
     * @param  array<string, mixed>  $slice
     * @return array<string, mixed>
     */
    protected function presentDuffelSlice(array $slice, string $label): array
    {
        $segments = $slice['segments'] ?? [];
        $first = $segments[0] ?? [];
        $last = $segments[array_key_last($segments)] ?? $first;
        $duration = $this->isoDurationMinutes($slice['duration'] ?? 'PT0M');
        $cabin = data_get($first, 'passengers.0.cabin_class', 'economy');

        return [
            'label' => $label,
            'airline' => AirlineCopy::airlineName(data_get($first, 'marketing_carrier.name')),
            'airline_code' => data_get($first, 'marketing_carrier.iata_code') ?? '',
            'flight_number' => trim((data_get($first, 'marketing_carrier.iata_code') ?? '').(data_get($first, 'marketing_carrier_flight_number') ?? '')),
            'cabin_class' => $cabin,
            'origin_code' => data_get($first, 'origin.iata_code'),
            'origin_city' => data_get($first, 'origin.city_name') ?? data_get($first, 'origin.city.name') ?? data_get($first, 'origin.name'),
            'origin_name' => data_get($first, 'origin.name'),
            'destination_code' => data_get($last, 'destination.iata_code'),
            'destination_city' => data_get($last, 'destination.city_name') ?? data_get($last, 'destination.city.name') ?? data_get($last, 'destination.name'),
            'destination_name' => data_get($last, 'destination.name'),
            'departure_at' => Carbon::parse($first['departing_at'] ?? now()),
            'arrival_at' => Carbon::parse($last['arriving_at'] ?? now()),
            'duration_minutes' => $duration,
            'formatted_duration' => $this->formatMinutes($duration),
            'stops' => max(count($segments) - 1, 0),
            'via' => $this->viaAirports($segments),
        ];
    }

    protected function searchLocal(array $filters, int $passengers): Collection
    {
        $outbound = $this->localFlights($filters, $passengers, $filters['from'] ?? null, $filters['to'] ?? null, $filters['date'] ?? null);
        $returnDate = static::returnDate($filters);

        if (! $returnDate) {
            return $outbound->map(fn (Flight $flight) => $this->presentLocalOffer($flight));
        }

        $inbound = $this->localFlights($filters, $passengers, $filters['to'] ?? null, $filters['from'] ?? null, $returnDate);

        if ($inbound->isEmpty()) {
            return $outbound->map(fn (Flight $flight) => $this->presentLocalOffer($flight));
        }

        $pairs = collect();

        foreach ($outbound->take(8) as $out) {
            foreach ($inbound->take(8) as $in) {
                $pairs->push($this->presentLocalOffer($out, $in));
            }
        }

        return $pairs->take(20)->values();
    }

    protected function localFlights(array $filters, int $passengers, mixed $from, mixed $to, ?string $date): Collection
    {
        $query = Flight::query()
            ->with(['airline', 'originAirport', 'destinationAirport'])
            ->searchable()
            ->where('available_seats', '>=', $passengers);

        if (! empty($from) && is_numeric($from)) {
            $query->where('origin_airport_id', (int) $from);
        } elseif ($code = $this->airportCode($from)) {
            $query->whereHas('originAirport', fn ($q) => $q->where('code', $code));
        }

        if (! empty($to) && is_numeric($to)) {
            $query->where('destination_airport_id', (int) $to);
        } elseif ($code = $this->airportCode($to)) {
            $query->whereHas('destinationAirport', fn ($q) => $q->where('code', $code));
        }

        if ($date) {
            $query->onDate($date);
        }

        if (! empty($filters['cabin'])) {
            $query->where('cabin_class', $filters['cabin']);
        }

        match ($filters['sort'] ?? 'price') {
            'duration' => $query->orderBy('duration_minutes'),
            'departure' => $query->orderBy('departure_at'),
            default => $query->orderBy('price'),
        };

        return $query->limit(40)->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentLocalOffer(Flight $outbound, ?Flight $inbound = null): array
    {
        $legs = [$this->presentLocalLeg($outbound, 'Outbound')];

        if ($inbound) {
            $legs[] = $this->presentLocalLeg($inbound, 'Return');
        }

        $first = $legs[0];
        $cabin = $outbound->cabin_class;
        $presented = array_merge($first, [
            'source' => 'local',
            'id' => $outbound->id,
            'return_flight_id' => $inbound?->id,
            'airline' => $outbound->airline->name,
            'airline_code' => $outbound->airline->code,
            'cabin_class' => $cabin,
            'cabin_label' => AirlineCopy::cabinLabel($cabin),
            'price' => (float) $outbound->price + (float) ($inbound?->price ?? 0),
            'currency' => 'INR',
            'available_seats' => min($outbound->available_seats, $inbound->available_seats ?? $outbound->available_seats),
            'model' => $outbound,
            'return_model' => $inbound,
            'legs' => $legs,
            'is_round_trip' => $inbound !== null,
            'terms' => \App\Support\FareFamily::termsForLocal((string) $cabin),
        ]);
        $presented['fingerprint'] = \App\Support\FareFamily::fingerprint($presented);

        return $presented;
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentLocalLeg(Flight $flight, string $label): array
    {
        $flight->loadMissing(['airline', 'originAirport', 'destinationAirport']);

        return [
            'label' => $label,
            'airline' => $flight->airline->name,
            'airline_code' => $flight->airline->code,
            'flight_number' => $flight->full_flight_number,
            'cabin_class' => $flight->cabin_class,
            'origin_code' => $flight->originAirport->code,
            'origin_city' => $flight->originAirport->city,
            'origin_name' => $flight->originAirport->name,
            'destination_code' => $flight->destinationAirport->code,
            'destination_city' => $flight->destinationAirport->city,
            'destination_name' => $flight->destinationAirport->name,
            'departure_at' => $flight->departure_at,
            'arrival_at' => $flight->arrival_at,
            'duration_minutes' => $flight->duration_minutes,
            'formatted_duration' => $flight->formatted_duration,
            'stops' => 0,
            'via' => [],
        ];
    }

    public function airportCode(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Airport::query()->whereKey($value)->value('code');
        }

        $code = strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{3}$/', $code) ? $code : null;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<string>
     */
    protected function viaAirports(array $segments): array
    {
        if (count($segments) < 2) {
            return [];
        }

        $codes = [];

        foreach (array_slice($segments, 0, -1) as $segment) {
            $code = data_get($segment, 'destination.iata_code');
            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    protected function cabin(?string $cabin): ?string
    {
        $allowed = ['economy', 'premium_economy', 'business', 'first'];

        return in_array($cabin, $allowed, true) ? $cabin : null;
    }

    protected function isoDurationMinutes(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);
        } catch (\Exception) {
            return 0;
        }

        return ($interval->d * 24 * 60) + ($interval->h * 60) + $interval->i;
    }

    protected function formatMinutes(int $minutes): string
    {
        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
