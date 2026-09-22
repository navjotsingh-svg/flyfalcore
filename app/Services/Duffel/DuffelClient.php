<?php

namespace App\Services\Duffel;

use App\Exceptions\DuffelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuffelClient
{
    public function configured(): bool
    {
        return filled(config('services.duffel.token'));
    }

    public function createOfferRequest(array $payload): array
    {
        return $this->request('post', '/air/offer_requests', [
            'return_offers' => 'false',
            'supplier_timeout' => (string) config('services.duffel.supplier_timeout', 15000),
        ], ['data' => $payload]);
    }

    public function listOffers(string $offerRequestId, int $limit = 50, string $sort = 'total_amount'): array
    {
        $response = $this->request('get', '/air/offers', [
            'offer_request_id' => $offerRequestId,
            'limit' => $limit,
            'sort' => $sort,
        ]);

        return $response['data'] ?? [];
    }

    public function getOffer(string $offerId, bool $withServices = true): array
    {
        $query = $withServices ? ['return_available_services' => 'true'] : [];
        $response = $this->request('get', '/air/offers/'.$offerId, $query);

        return $response['data'] ?? [];
    }

    public function getSeatMaps(string $offerId): array
    {
        try {
            $response = $this->request('get', '/air/seat_maps', [
                'offer_id' => $offerId,
            ]);

            return $response['data'] ?? [];
        } catch (DuffelException $exception) {
            Log::info('Seat map unavailable', [
                'offer' => $offerId,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function createOrder(array $payload): array
    {
        $response = $this->request('post', '/air/orders', [], ['data' => $payload]);

        return $response['data'] ?? [];
    }

    public function suggestPlaces(string $query): array
    {
        $response = $this->request('get', '/places/suggestions', [
            'query' => $query,
        ]);

        return $response['data'] ?? [];
    }

    protected function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        if (! $this->configured()) {
            throw new DuffelException('Live airline search is not configured.');
        }

        $pending = $this->http();

        try {
            $response = $body === null
                ? $pending->{$method}($path, $query)
                : $pending->withQueryParameters($query)->{$method}($path, $body);
        } catch (ConnectionException $exception) {
            Log::warning('Duffel connection failed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw new DuffelException(
                'Could not reach live airline search. Check your internet connection or try again shortly.',
                0,
                ['exception' => $exception->getMessage()],
            );
        } catch (RequestException $exception) {
            $errors = $exception->response?->json('errors') ?? [];

            throw new DuffelException(
                $this->formatErrors($errors, 'Airline request failed.'),
                $exception->response?->status() ?? 0,
                is_array($errors) ? $errors : [],
            );
        }

        if ($response->failed()) {
            $errors = $response->json('errors') ?? [];

            Log::warning('Duffel API error', [
                'path' => $path,
                'status' => $response->status(),
                'errors' => $errors,
            ]);

            throw new DuffelException(
                $this->formatErrors($errors, 'Airline request failed.'),
                $response->status(),
                is_array($errors) ? $errors : [],
            );
        }

        return $response->json() ?? [];
    }

    protected function formatErrors(array $errors, string $fallback): string
    {
        $error = $errors[0] ?? [];
        $message = $error['message'] ?? $error['detail'] ?? $error['title'] ?? $fallback;

        if (! is_string($message) || $message === '') {
            $message = $fallback;
        }

        $pointer = data_get($error, 'source.pointer');
        $field = data_get($error, 'source.field');

        if (is_string($pointer) && $pointer !== '' && ! str_contains(strtolower($message), strtolower((string) $field))) {
            $message .= ' ('.$pointer.')';
        }

        return \App\Support\AirlineCopy::publicMessage($message);
    }

    protected function http(): PendingRequest
    {
        $host = parse_url((string) config('services.duffel.base_url'), PHP_URL_HOST) ?: 'api.duffel.com';

        return Http::baseUrl(rtrim((string) config('services.duffel.base_url'), '/'))
            ->timeout((int) config('services.duffel.timeout', 45))
            ->retry(2, 250, fn ($exception) => $exception instanceof ConnectionException, false)
            ->acceptJson()
            ->asJson()
            ->withOptions([
                'curl' => $this->curlResolveOptions($host),
            ])
            ->withHeaders([
                'Authorization' => 'Bearer '.config('services.duffel.token'),
                'Duffel-Version' => config('services.duffel.version', 'v2'),
            ]);
    }

    /**
     * XAMPP Apache's cURL often fails to resolve hosts (error 6) while PHP DNS works.
     * Pin an IPv4 address so offer search can still reach Duffel.
     *
     * @return array<int, mixed>
     */
    protected function curlResolveOptions(string $host): array
    {
        $options = [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];

        $ip = $this->resolveHost($host);

        if ($ip) {
            $options[CURLOPT_RESOLVE] = [$host.':443:'.$ip];
        }

        return $options;
    }

    protected function resolveHost(string $host): ?string
    {
        $resolved = gethostbyname($host);

        if (is_string($resolved) && $resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP)) {
            return $resolved;
        }

        return '104.18.7.92';
    }
}
