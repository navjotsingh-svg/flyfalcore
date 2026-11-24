<?php

namespace App\Services\Tbo;

use App\Exceptions\TboException;
use App\Support\AirlineCopy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TboClient
{
    public function configured(): bool
    {
        return filled(config('services.tbo.username'))
            && filled(config('services.tbo.password'))
            && filled(config('services.tbo.client_id'));
    }

    public function authenticate(bool $force = false): string
    {
        if (! $this->configured()) {
            throw new TboException('Live airline search is not configured.');
        }

        $cacheKey = 'tbo.token';

        if (! $force && Cache::has($cacheKey)) {
            return (string) Cache::get($cacheKey);
        }

        $payload = [
            'ClientId' => config('services.tbo.client_id'),
            'UserName' => config('services.tbo.username'),
            'Password' => config('services.tbo.password'),
            'EndUserIp' => $this->endUserIp(),
        ];

        $response = $this->send('post', $this->authUrl('/Authenticate'), $payload, retryAuth: false);
        $token = (string) ($response['TokenId'] ?? data_get($response, 'Response.TokenId', ''));

        if ($token === '') {
            throw new TboException('Airline search could not start a session.');
        }

        Cache::put($cacheKey, $token, now()->addHours(15));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function search(array $payload): array
    {
        return $this->air('Search', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function fareQuote(string $traceId, string $resultIndex): array
    {
        return $this->air('FareQuote', [
            'EndUserIp' => $this->endUserIp(),
            'TokenId' => $this->authenticate(),
            'TraceId' => $traceId,
            'ResultIndex' => $resultIndex,
        ]);
    }

    /**
     * Reprice a Search result. TBO requires this before Book / Ticket.
     *
     * @return array<string, mixed>
     */
    public function quotedItinerary(string $traceId, string $resultIndex): array
    {
        $quoted = $this->firstResult($this->fareQuote($traceId, $resultIndex));

        if ($quoted === [] || blank($quoted['ResultIndex'] ?? $quoted['Fare'] ?? null)) {
            throw new TboException('This fare is no longer available. Search again to see live prices.');
        }

        return $quoted;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function firstResult(array $payload): array
    {
        $results = data_get($payload, 'Response.Results', data_get($payload, 'Results'));

        if (! is_array($results) || $results === []) {
            return [];
        }

        if (isset($results['ResultIndex']) || isset($results['Fare'])) {
            return $results;
        }

        $first = $results[0] ?? [];

        if (isset($first['ResultIndex']) || isset($first['Fare'])) {
            return $first;
        }

        return is_array($first[0] ?? null) ? $first[0] : (is_array($first) ? $first : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function ssr(string $traceId, string $resultIndex): array
    {
        return $this->air('SSR', [
            'EndUserIp' => $this->endUserIp(),
            'TokenId' => $this->authenticate(),
            'TraceId' => $traceId,
            'ResultIndex' => $resultIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function book(array $payload): array
    {
        return $this->air('Book', $this->withSession($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function ticket(array $payload): array
    {
        return $this->air('Ticket', $this->withSession($payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function bookingDetails(string $bookingId, ?string $pnr = null): array
    {
        return $this->air('GetBookingDetails', array_filter([
            'EndUserIp' => $this->endUserIp(),
            'TokenId' => $this->authenticate(),
            'BookingId' => $bookingId,
            'PNR' => $pnr,
        ], fn ($value) => filled($value)));
    }

    /**
     * Full cancellation of a ticketed booking.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $bookingId, int $requestType = 1, int $cancellationType = 3): array
    {
        return $this->air('Cancel', [
            'EndUserIp' => $this->endUserIp(),
            'TokenId' => $this->authenticate(),
            'BookingId' => $bookingId,
            'RequestType' => $requestType,
            'CancellationType' => $cancellationType,
        ]);
    }

    public function endUserIp(): string
    {
        $configured = trim((string) config('services.tbo.end_user_ip', ''));
        if ($configured !== '') {
            return $configured;
        }

        $ip = request()?->ip();

        return is_string($ip) && $ip !== '' ? $ip : '127.0.0.1';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function air(string $method, array $payload): array
    {
        $payload = $this->withSession($payload);

        try {
            return $this->send('post', $this->airUrl('/'.$method), $payload);
        } catch (TboException $exception) {
            if (! $this->isExpiredSession($exception)) {
                throw $exception;
            }

            Cache::forget('tbo.token');
            $payload['TokenId'] = $this->authenticate(true);

            return $this->send('post', $this->airUrl('/'.$method), $payload, retryAuth: false);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withSession(array $payload): array
    {
        $payload['EndUserIp'] = $payload['EndUserIp'] ?? $this->endUserIp();
        $payload['TokenId'] = $payload['TokenId'] ?? $this->authenticate();

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function send(string $method, string $url, array $payload, bool $retryAuth = true): array
    {
        try {
            $response = $this->http()->{$method}($url, $payload);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('TBO connection failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            throw new TboException(
                'Could not reach live airline search. Check your internet connection or try again shortly.',
                (int) ($exception instanceof RequestException ? $exception->response?->status() : 0),
                ['exception' => $exception->getMessage()],
            );
        }

        $json = $response->json();
        $data = is_array($json) ? $json : [];

        if ($response->failed() || $this->hasError($data)) {
            $message = $this->errorMessage($data, $response->status());

            Log::warning('TBO API error', [
                'url' => $url,
                'status' => $response->status(),
                'message' => $message,
            ]);

            throw new TboException(AirlineCopy::publicMessage($message), $response->status(), $data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasError(array $data): bool
    {
        $code = (int) ($data['Error']['ErrorCode'] ?? data_get($data, 'Response.Error.ErrorCode', 0));
        $status = (int) ($data['ResponseStatus'] ?? data_get($data, 'Response.ResponseStatus', 1));
        $success = $data['IsSuccess'] ?? null;

        return $code > 0 || $status === 2 || $success === false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function errorMessage(array $data, int $httpStatus): string
    {
        $message = $data['Error']['ErrorMessage']
            ?? data_get($data, 'Response.Error.ErrorMessage')
            ?? $data['ErrorMessage']
            ?? null;

        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        return $httpStatus >= 400
            ? 'Airline request failed.'
            : 'Airline search returned no fares.';
    }

    protected function isExpiredSession(TboException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'token')
            || str_contains($message, 'session')
            || str_contains($message, 'authenticate');
    }

    protected function authUrl(string $path): string
    {
        return rtrim((string) config('services.tbo.auth_url'), '/').$path;
    }

    protected function airUrl(string $path): string
    {
        return rtrim((string) config('services.tbo.air_url'), '/').$path;
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('services.tbo.timeout', 60))
            ->retry(1, 250, fn ($exception) => $exception instanceof ConnectionException, false)
            ->acceptJson()
            ->asJson();
    }
}
