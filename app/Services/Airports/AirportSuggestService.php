<?php

namespace App\Services\Airports;

use App\Models\Airport;
use App\Services\Duffel\DuffelClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirportSuggestService
{
    public function __construct(protected DuffelClient $duffel) {}

    public function suggest(string $query, int $limit = 8): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return collect();
        }

        $matches = $this->localMatches($query);

        if ($this->duffel->configured()) {
            $matches = $matches->concat($this->duffelMatches($query));
        }

        return $matches
            ->unique('code')
            ->sortBy(fn (array $airport) => $this->rank($airport, $query))
            ->take($limit)
            ->values();
    }

    public function labelFor(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        $airport = is_numeric($value)
            ? Airport::query()->find($value)
            : Airport::query()->where('code', strtoupper((string) $value))->first();

        if ($airport) {
            return "{$airport->city} ({$airport->code})";
        }

        $code = strtoupper(trim((string) $value));
        $catalog = $this->catalog()->firstWhere('code', $code);

        return $catalog
            ? "{$catalog['city']} ({$catalog['code']})"
            : $code;
    }

    protected function localMatches(string $query): Collection
    {
        $needle = mb_strtolower($query);

        $fromDatabase = Airport::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($query) {
                $builder->where('code', 'like', $query.'%')
                    ->orWhere('city', 'like', '%'.$query.'%')
                    ->orWhere('name', 'like', '%'.$query.'%')
                    ->orWhere('country', 'like', '%'.$query.'%');
            })
            ->orderBy('city')
            ->limit(12)
            ->get()
            ->map(fn (Airport $airport) => [
                'code' => $airport->code,
                'city' => $airport->city,
                'name' => $airport->name,
                'country' => $airport->country,
                'label' => "{$airport->city} ({$airport->code})",
            ]);

        $fromCatalog = $this->catalog()
            ->filter(function (array $airport) use ($needle) {
                return str_contains(mb_strtolower($airport['code']), $needle)
                    || str_contains(mb_strtolower($airport['city']), $needle)
                    || str_contains(mb_strtolower($airport['name']), $needle)
                    || str_contains(mb_strtolower($airport['country']), $needle);
            })
            ->map(fn (array $airport) => [
                'code' => $airport['code'],
                'city' => $airport['city'],
                'name' => $airport['name'],
                'country' => $airport['country'],
                'label' => "{$airport['city']} ({$airport['code']})",
            ]);

        return $fromDatabase->concat($fromCatalog);
    }

    protected function duffelMatches(string $query): Collection
    {
        try {
            $places = $this->duffel->suggestPlaces($query);
        } catch (Throwable $exception) {
            Log::info('Airport suggestions fell back to the local catalog.', [
                'message' => $exception->getMessage(),
            ]);

            return collect();
        }

        return collect($places)
            ->filter(fn (array $place) => filled(data_get($place, 'iata_code')))
            ->map(function (array $place) {
                $code = strtoupper((string) data_get($place, 'iata_code'));
                $city = data_get($place, 'city_name')
                    ?? data_get($place, 'city.name')
                    ?? data_get($place, 'name');

                return [
                    'code' => $code,
                    'city' => $city,
                    'name' => data_get($place, 'name', $city),
                    'country' => data_get($place, 'iata_country_code') ?? data_get($place, 'country_name', ''),
                    'label' => "{$city} ({$code})",
                ];
            });
    }

    protected function catalog(): Collection
    {
        $path = resource_path('data/airports.json');

        if (! is_file($path)) {
            return collect();
        }

        return collect(json_decode((string) file_get_contents($path), true) ?: []);
    }

    protected function rank(array $airport, string $query): int
    {
        $needle = mb_strtolower($query);
        $code = mb_strtolower($airport['code']);
        $city = mb_strtolower($airport['city']);

        if ($code === $needle) {
            return 0;
        }

        if (str_starts_with($code, $needle)) {
            return 1;
        }

        if ($city === $needle) {
            return 2;
        }

        if (str_starts_with($city, $needle)) {
            return 3;
        }

        return 4;
    }
}
