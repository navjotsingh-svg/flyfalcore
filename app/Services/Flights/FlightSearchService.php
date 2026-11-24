<?php

namespace App\Services\Flights;

use App\Exceptions\TboException;
use App\Models\Airport;
use App\Models\Flight;
use App\Services\Tbo\TboClient;
use App\Support\AirlineCopy;
use App\Support\FareFamily;
use App\Support\PassengerMix;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FlightSearchService
{
    public function __construct(
        protected TboClient $tbo,
    ) {}

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

        if ($this->tbo->configured() && $origin && $destination && $date) {
            try {
                return [
                    'source' => 'tbo',
                    'offers' => $this->searchTbo($origin, $destination, $date, $returnDate, $cabin, $mix, $filters['sort'] ?? 'price'),
                    'message' => null,
                ];
            } catch (TboException $exception) {
                Log::warning('TBO search failed, showing sample flights', [
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

        $searched = filled($origin) && filled($destination) && filled($date);

        return [
            'source' => 'local',
            'offers' => $this->searchLocal($filters, $mix->seatedCount()),
            'message' => $searched
                ? ($this->liveConfigured() ? null : 'Live airline search is not configured. Showing sample flights for now.')
                : ($this->liveConfigured()
                    ? 'Choose origin, destination, and date to search live airline fares.'
                    : 'Live airline search is not configured. Showing sample flights for now.'),
        ];
    }

    public function liveConfigured(): bool
    {
        return $this->tbo->configured();
    }

    /**
     * @return array<string, mixed>
     */
    public function liveOffer(string $id): array
    {
        if (str_starts_with($id, 'tbo-')) {
            return $this->cachedTboOffer($id);
        }

        throw new TboException('This fare has expired. Search again to see live prices.');
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return list<array<string, mixed>>
     */
    public function liveFareFamily(array $offer): array
    {
        if (($offer['source'] ?? '') !== 'tbo') {
            return [$offer];
        }

        $traceId = (string) ($offer['tbo_trace_id'] ?? '');
        $siblings = collect(Cache::get($this->tboTraceCacheKey($traceId), []))
            ->map(fn (array $row) => $this->presentTboOffer(
                $row['raw'],
                $row['trace_id'],
                $row['id'],
                $row['return_raw'] ?? null,
            ))
            ->filter(fn (array $row) => ($row['fingerprint'] ?? '') === ($offer['fingerprint'] ?? ''))
            ->map(fn (array $row) => $row['id'] === ($offer['id'] ?? null) ? $offer : $row)
            ->values();

        return $siblings->isNotEmpty() ? $siblings->all() : [$offer];
    }

    /**
     * @return array<string, mixed>
     */
    public function cachedTboOffer(string $id): array
    {
        $cached = Cache::get($this->tboOfferCacheKey($id));

        if (! is_array($cached) || empty($cached['raw'])) {
            throw new TboException('This fare has expired. Search again to see live prices.');
        }

        $traceId = (string) $cached['trace_id'];
        $quoted = $this->tbo->quotedItinerary($traceId, (string) $cached['result_index']);
        $cached['raw'] = array_replace($cached['raw'], $quoted);
        $cached['result_index'] = (string) ($quoted['ResultIndex'] ?? $cached['result_index']);

        if (filled($cached['return_result_index'] ?? null)) {
            $returnQuoted = $this->tbo->quotedItinerary($traceId, (string) $cached['return_result_index']);
            $cached['return_raw'] = array_replace($cached['return_raw'] ?? [], $returnQuoted);
            $cached['return_result_index'] = (string) ($returnQuoted['ResultIndex'] ?? $cached['return_result_index']);
        }

        Cache::put($this->tboOfferCacheKey($id), $cached, now()->addMinutes(20));

        return $this->presentTboOffer(
            $cached['raw'],
            $traceId,
            $id,
            $cached['return_raw'] ?? null,
            PassengerMix::fromArray($cached['mix'] ?? ['adults' => 1]),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchTbo(
        string $origin,
        string $destination,
        string $date,
        ?string $returnDate,
        ?string $cabin,
        PassengerMix $mix,
        string $sort,
    ): Collection {
        $segments = [[
            'Origin' => $origin,
            'Destination' => $destination,
            'FlightCabinClass' => $this->tboCabinClass($cabin),
            'PreferredDepartureTime' => Carbon::parse($date)->startOfDay()->format('Y-m-d\TH:i:s'),
        ]];

        if (filled($returnDate)) {
            $segments[] = [
                'Origin' => $destination,
                'Destination' => $origin,
                'FlightCabinClass' => $this->tboCabinClass($cabin),
                'PreferredDepartureTime' => Carbon::parse($returnDate)->startOfDay()->format('Y-m-d\TH:i:s'),
            ];
        }

        $response = $this->tbo->search([
            'AdultCount' => $mix->adults,
            'ChildCount' => $mix->children,
            'InfantCount' => $mix->infants,
            'JourneyType' => filled($returnDate) ? 2 : 1,
            'DirectFlight' => false,
            'OneStopFlight' => false,
            'PreferredAirlines' => null,
            'Sources' => null,
            'Segments' => $segments,
        ]);

        $traceId = (string) data_get($response, 'Response.TraceId', data_get($response, 'TraceId', ''));
        [$outbound, $inbound] = $this->tboResultGroups($response);
        $offers = collect();

        if ($inbound->isNotEmpty()) {
            foreach ($outbound->take(8) as $out) {
                foreach ($inbound->take(8) as $in) {
                    $offers->push($this->storeTboOffer($traceId, $out, $in, $mix));
                }
            }
        } else {
            foreach ($outbound->take(40) as $row) {
                $offers->push($this->storeTboOffer($traceId, $row, null, $mix));
            }
        }

        $offers = $offers->filter()->values();

        Cache::put($this->tboTraceCacheKey($traceId), $offers->map(fn (array $offer) => [
            'id' => $offer['id'],
            'trace_id' => $traceId,
            'raw' => $offer['raw'],
            'return_raw' => $offer['return_raw'] ?? null,
        ])->all(), now()->addMinutes(25));

        return $sort === 'duration'
            ? $offers->sortBy('duration_minutes')->values()
            : $offers->sortBy('price')->values();
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{0: Collection<int, array<string, mixed>>, 1: Collection<int, array<string, mixed>>}
     */
    protected function tboResultGroups(array $response): array
    {
        $results = data_get($response, 'Response.Results', data_get($response, 'Results', []));

        if (! is_array($results) || $results === []) {
            return [collect(), collect()];
        }

        $first = $results[0] ?? null;

        if ($this->isTboOffer($first)) {
            return [
                collect($results)->filter(fn ($row) => $this->isTboOffer($row))->values(),
                collect(),
            ];
        }

        return [
            collect(is_array($first) ? $first : [])->filter(fn ($row) => $this->isTboOffer($row))->values(),
            collect(is_array($results[1] ?? null) ? $results[1] : [])->filter(fn ($row) => $this->isTboOffer($row))->values(),
        ];
    }

    protected function isTboOffer(mixed $row): bool
    {
        return is_array($row) && (isset($row['ResultIndex']) || isset($row['Fare']) || isset($row['Segments']));
    }

    /**
     * @param  array<string, mixed>  $outbound
     * @param  array<string, mixed>|null  $inbound
     * @return array<string, mixed>
     */
    protected function storeTboOffer(string $traceId, array $outbound, ?array $inbound, PassengerMix $mix): array
    {
        $resultIndex = (string) ($outbound['ResultIndex'] ?? '');
        $returnIndex = (string) ($inbound['ResultIndex'] ?? '');
        $id = 'tbo-'.substr(sha1($traceId.'|'.$resultIndex.'|'.$returnIndex), 0, 16);
        $presented = $this->presentTboOffer($outbound, $traceId, $id, $inbound, $mix);

        Cache::put($this->tboOfferCacheKey($id), [
            'trace_id' => $traceId,
            'result_index' => $resultIndex,
            'return_result_index' => $returnIndex !== '' ? $returnIndex : null,
            'raw' => $outbound,
            'return_raw' => $inbound,
            'mix' => $mix->query(),
        ], now()->addMinutes(25));

        return $presented;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>|null  $returnRaw
     * @return array<string, mixed>
     */
    public function presentTboOffer(array $raw, string $traceId, string $id, ?array $returnRaw = null, ?PassengerMix $mix = null): array
    {
        $outboundSegments = $raw['Segments'][0] ?? [];
        $returnSegments = $returnRaw['Segments'][0] ?? ($raw['Segments'][1] ?? []);
        $legs = [$this->presentTboLeg($outboundSegments, 'Outbound')];

        if (is_array($returnSegments) && $returnSegments !== []) {
            $legs[] = $this->presentTboLeg($returnSegments, 'Return');
        }

        $firstLeg = $legs[0];
        $currency = strtoupper((string) ($raw['Fare']['Currency'] ?? config('services.tbo.currency', 'INR') ?: 'INR'));
        $price = $this->tboPrice($raw) + ($returnRaw ? $this->tboPrice($returnRaw) : 0);
        $cabin = FareFamily::tboCabin($raw);
        $mix ??= PassengerMix::fromArray(['adults' => 1]);

        $presented = array_merge($firstLeg, [
            'source' => 'tbo',
            'id' => $id,
            'offer_request_id' => $traceId,
            'tbo_trace_id' => $traceId,
            'tbo_result_index' => (string) ($raw['ResultIndex'] ?? ''),
            'tbo_return_result_index' => (string) ($returnRaw['ResultIndex'] ?? ''),
            'is_lcc' => (bool) ($raw['IsLCC'] ?? $returnRaw['IsLCC'] ?? false),
            'airline' => AirlineCopy::airlineName($firstLeg['airline'] ?? null),
            'airline_code' => $firstLeg['airline_code'] ?? '',
            'cabin_class' => $cabin,
            'cabin_label' => AirlineCopy::cabinLabel($cabin),
            'available_services' => [],
            'price' => $price,
            'currency' => $currency,
            'passengers' => $mix->passengerPayload(),
            'expires_at' => now()->addMinutes(20),
            'slices' => [],
            'legs' => $legs,
            'is_round_trip' => count($legs) > 1,
            'terms' => FareFamily::termsFromTbo($raw, $currency),
            'raw' => $raw,
            'return_raw' => $returnRaw,
        ]);
        $presented['fingerprint'] = FareFamily::fingerprint($presented);

        return $presented;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return array<string, mixed>
     */
    protected function presentTboLeg(array $segments, string $label): array
    {
        $first = $segments[0] ?? [];
        $last = $segments[array_key_last($segments)] ?? $first;
        $airline = $first['Airline'] ?? [];
        $duration = (int) collect($segments)->sum(fn (array $segment) => (int) ($segment['Duration'] ?? 0));
        if ($duration === 0) {
            $duration = (int) ($last['AccumulatedDuration'] ?? 0);
        }

        $depart = Carbon::parse(data_get($first, 'Origin.DepTime') ?? now());
        $arrive = Carbon::parse(data_get($last, 'Destination.ArrTime') ?? now());

        return [
            'label' => $label,
            'airline' => AirlineCopy::airlineName($airline['AirlineName'] ?? null),
            'airline_code' => (string) ($airline['AirlineCode'] ?? ''),
            'flight_number' => trim(($airline['AirlineCode'] ?? '').($airline['FlightNumber'] ?? '')),
            'cabin_class' => FareFamily::tboCabin(['Segments' => [$segments]]),
            'origin_code' => data_get($first, 'Origin.Airport.AirportCode'),
            'origin_city' => data_get($first, 'Origin.Airport.CityName') ?? data_get($first, 'Origin.Airport.AirportName'),
            'origin_name' => data_get($first, 'Origin.Airport.AirportName'),
            'destination_code' => data_get($last, 'Destination.Airport.AirportCode'),
            'destination_city' => data_get($last, 'Destination.Airport.CityName') ?? data_get($last, 'Destination.Airport.AirportName'),
            'destination_name' => data_get($last, 'Destination.Airport.AirportName'),
            'departure_at' => $depart,
            'arrival_at' => $arrive,
            'duration_minutes' => $duration,
            'formatted_duration' => $this->formatMinutes($duration),
            'stops' => max(count($segments) - 1, 0),
            'via' => collect($segments)->slice(0, -1)->map(fn (array $segment) => data_get($segment, 'Destination.Airport.AirportCode'))->filter()->unique()->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function tboPrice(array $raw): float
    {
        $fare = $raw['Fare'] ?? [];

        foreach (['PublishedFare', 'OfferedFare', 'BaseFare'] as $key) {
            if (isset($fare[$key]) && is_numeric($fare[$key])) {
                return round((float) $fare[$key], 2);
            }
        }

        return 0.0;
    }

    protected function tboCabinClass(?string $cabin): int
    {
        return match ($cabin) {
            'premium_economy' => 3,
            'business' => 4,
            'first' => 6,
            'economy' => 2,
            default => 1,
        };
    }

    protected function tboOfferCacheKey(string $id): string
    {
        return 'tbo.offer.'.$id;
    }

    protected function tboTraceCacheKey(string $traceId): string
    {
        return 'tbo.trace.'.$traceId;
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
    protected function cabin(?string $cabin): ?string
    {
        $allowed = ['economy', 'premium_economy', 'business', 'first'];

        return in_array($cabin, $allowed, true) ? $cabin : null;
    }

    protected function formatMinutes(int $minutes): string
    {
        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
