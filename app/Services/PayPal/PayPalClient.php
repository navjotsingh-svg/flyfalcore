<?php

namespace App\Services\PayPal;

use App\Exceptions\PayPalException;
use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalClient
{
    public function configured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'));
    }

    public function createOrder(Booking $booking): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $booking->booking_reference,
                'description' => 'Falcore flight '.$booking->booking_reference,
                'custom_id' => $booking->booking_reference,
                'amount' => [
                    'currency_code' => $booking->paypalCurrency(),
                    'value' => $booking->paypalAmount(),
                ],
            ]],
            'application_context' => [
                'brand_name' => config('app.name', 'Falcore'),
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => route('paypal.success', $booking->booking_reference),
                'cancel_url' => route('paypal.cancel', $booking->booking_reference),
            ],
        ];

        $order = $this->request('post', '/v2/checkout/orders', $payload);
        $approve = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        if (! is_array($approve) || empty($approve['href'])) {
            throw new PayPalException('PayPal did not return an approval URL.');
        }

        return [
            'id' => $order['id'],
            'approve_url' => $approve['href'],
            'raw' => $order,
        ];
    }

    public function captureOrder(string $orderId): array
    {
        return $this->request('post', '/v2/checkout/orders/'.$orderId.'/capture');
    }

    public function getOrder(string $orderId): array
    {
        return $this->request('get', '/v2/checkout/orders/'.$orderId);
    }

    public function isCaptured(array $captureResponse): bool
    {
        if (($captureResponse['status'] ?? '') === 'COMPLETED') {
            return true;
        }

        $captures = data_get($captureResponse, 'purchase_units.0.payments.captures', []);

        return collect($captures)->contains(fn ($capture) => ($capture['status'] ?? '') === 'COMPLETED');
    }

    public function captureId(array $captureResponse): ?string
    {
        return data_get($captureResponse, 'purchase_units.0.payments.captures.0.id');
    }

    protected function request(string $method, string $path, mixed $body = null): array
    {
        if (! $this->configured()) {
            throw new PayPalException('PayPal is not configured. Add PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET.');
        }

        $pending = Http::baseUrl($this->baseUrl())
            ->timeout(30)
            ->acceptJson()
            ->withToken($this->accessToken());

        $response = match (true) {
            $method === 'post' && $body === null => $pending->withBody('{}', 'application/json')->post($path),
            $body === null => $pending->{$method}($path),
            default => $pending->asJson()->{$method}($path, $body),
        };

        if ($response->failed()) {
            Log::warning('PayPal API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $message = $response->json('message')
                ?? $response->json('error_description')
                ?? $response->json('details.0.description')
                ?? 'PayPal request failed.';

            throw new PayPalException($message, $response->status(), $response->json() ?? []);
        }

        return $response->json() ?? [];
    }

    protected function accessToken(): string
    {
        $cacheKey = 'paypal.access_token.'.md5((string) config('services.paypal.client_id'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->withBasicAuth(
                    (string) config('services.paypal.client_id'),
                    (string) config('services.paypal.client_secret'),
                )
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new PayPalException('Unable to authenticate with PayPal.', $response->status(), $response->json() ?? []);
            }

            return (string) $response->json('access_token');
        });
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.paypal.base_url'), '/');
    }
}
