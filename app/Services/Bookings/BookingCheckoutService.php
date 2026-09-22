<?php

namespace App\Services\Bookings;

use App\Exceptions\DuffelException;
use App\Exceptions\PayPalException;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Services\Duffel\DuffelClient;
use App\Services\PayPal\PayPalClient;
use App\Support\AirlineCopy;
use App\Support\PassengerMix;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingCheckoutService
{
    public function __construct(
        protected PayPalClient $paypal,
        protected DuffelClient $duffel,
    ) {}

    public function startPayPal(Booking $booking): string
    {
        $order = $this->paypal->createOrder($booking);

        $booking->update([
            'paypal_order_id' => $order['id'],
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        return $order['approve_url'];
    }

    public function completePayPal(Booking $booking, ?string $paypalOrderId = null): Booking
    {
        if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
            return $booking;
        }

        $orderId = $paypalOrderId ?: $booking->paypal_order_id;

        if (blank($orderId)) {
            throw new PayPalException('Missing PayPal order for this booking.');
        }

        if ($booking->payment_status !== 'paid') {
            $captured = $this->paypal->captureOrder($orderId);

            if (! $this->paypal->isCaptured($captured)) {
                throw new PayPalException('PayPal payment was not completed.');
            }

            $booking->update([
                'paypal_order_id' => $orderId,
                'paypal_capture_id' => $this->paypal->captureId($captured),
                'payment_status' => 'paid',
                'payment_payload' => $captured,
            ]);
        }

        $booking->refresh();

        if ($booking->source === 'duffel' && blank($booking->duffel_order_id)) {
            $this->createDuffelOrder($booking);
        } else {
            $booking->update([
                'status' => 'confirmed',
                'booked_at' => $booking->booked_at ?? now(),
            ]);
        }

        $booking = $booking->fresh(['flight.airline', 'flight.originAirport', 'flight.destinationAirport', 'passengers']);
        $this->sendConfirmation($booking);

        return $booking;
    }

    public function createDuffelOrder(Booking $booking): Booking
    {
        if (blank($booking->duffel_offer_id)) {
            throw new DuffelException('This booking has no airline offer to confirm.');
        }

        $booking->loadMissing('passengers');

        $offer = $this->duffel->getOffer($booking->duffel_offer_id);
        $airlineExtra = (float) data_get($booking->itinerary, 'extras.airline_total', 0);
        $amount = number_format(((float) ($offer['total_amount'] ?? $booking->total_amount)) + $airlineExtra, 2, '.', '');
        $currency = (string) ($offer['total_currency'] ?? $booking->currency);
        $passengerPayload = $this->duffelPassengers($booking, $offer);

        $payload = [
            'selected_offers' => [$booking->duffel_offer_id],
            'passengers' => $passengerPayload,
            'metadata' => [
                'booking_reference' => $booking->booking_reference,
            ],
        ];

        $services = data_get($booking->itinerary, 'extras.services', []);
        if (is_array($services) && $services !== []) {
            $payload['services'] = array_values($services);
        }

        $order = null;
        $lastException = null;

        foreach ($this->duffelPaymentAttempts($offer, $amount, $currency) as $attempt) {
            try {
                $order = $this->duffel->createOrder(array_merge($payload, $attempt));
                $lastException = null;
                break;
            } catch (DuffelException $exception) {
                $lastException = $exception;

                if (! $this->isRetryablePaymentError($exception)) {
                    break;
                }
            }
        }

        if ($order === null) {
            $exception = $lastException ?? new DuffelException('The airline could not issue this ticket.');

            $booking->update([
                'status' => 'fulfillment_failed',
                'fulfillment_error' => AirlineCopy::publicMessage($exception->getMessage()),
            ]);

            Log::error('Duffel order failed after PayPal capture', [
                'booking' => $booking->booking_reference,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors ?? [],
            ]);

            throw $exception;
        }

        $booking->update([
            'status' => 'confirmed',
            'booked_at' => now(),
            'duffel_order_id' => $order['id'] ?? null,
            'duffel_booking_reference' => $order['booking_reference'] ?? data_get($order, 'booking_references.0.booking_reference'),
            'total_amount' => max((float) $booking->total_amount, (float) $amount),
            'currency' => $currency,
            'itinerary' => array_merge($booking->itinerary ?? [], [
                'airline_order' => [
                    'id' => $order['id'] ?? null,
                    'booking_reference' => $order['booking_reference'] ?? null,
                ],
            ]),
            'fulfillment_error' => null,
        ]);

        $this->sendConfirmation($booking->fresh(['passengers', 'flight.airline', 'flight.originAirport', 'flight.destinationAirport']));

        return $booking;
    }

    public function sendConfirmation(Booking $booking): void
    {
        $booking->refresh();

        if ($booking->status !== 'confirmed' || $booking->confirmation_sent_at || blank($booking->contact_email)) {
            return;
        }

        try {
            Mail::to($booking->contact_email)->send(new BookingConfirmed($booking));
            $booking->update(['confirmation_sent_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Booking confirmation email failed', [
                'booking' => $booking->booking_reference,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function duffelPaymentAttempts(array $offer, string $amount, string $currency): array
    {
        $available = collect(data_get($offer, 'available_payment_types', []))
            ->merge(collect(data_get($offer, 'available_payment_methods', []))->pluck('type'))
            ->filter()
            ->unique()
            ->values();

        $types = $available
            ->filter(fn ($type) => in_array($type, ['balance', 'arc_bsp_cash'], true))
            ->values();

        if ($types->isEmpty()) {
            $types = collect(['balance', 'arc_bsp_cash']);
        }

        $attempts = [];

        foreach ($types as $type) {
            $attempts[] = [
                'type' => 'instant',
                'payments' => [[
                    'type' => $type,
                    'amount' => $amount,
                    'currency' => $currency,
                ]],
            ];
        }

        // Some Duffel accounts reject the top-level order type and infer it from payments.
        $attempts[] = [
            'payments' => [[
                'type' => $types->first() ?: 'balance',
                'amount' => $amount,
                'currency' => $currency,
            ]],
        ];

        return $attempts;
    }

    protected function isRetryablePaymentError(DuffelException $exception): bool
    {
        $pointer = strtolower((string) data_get($exception->errors, '0.source.pointer', ''));
        $field = strtolower((string) data_get($exception->errors, '0.source.field', ''));
        $message = strtolower($exception->getMessage());

        return $field === 'type'
            || str_contains($pointer, 'payment')
            || str_contains($pointer, '/type')
            || str_contains($message, 'payment type')
            || str_contains($message, 'not valid with selected offer');
    }

    protected function duffelPassengers(Booking $booking, array $offer): array
    {
        $payload = [];

        foreach ($booking->passengers as $index => $passenger) {
            $offerPassenger = data_get($offer, 'passengers.'.$index, []);
            $duffelId = $passenger->duffel_passenger_id ?: ($offerPassenger['id'] ?? null);

            if (blank($duffelId)) {
                throw new DuffelException('Missing passenger id for '.$passenger->full_name.'.');
            }

            $row = [
                'id' => $duffelId,
                'title' => $passenger->title ?: $passenger->duffelTitle(),
                'gender' => $passenger->duffelGender(),
                'given_name' => $passenger->first_name,
                'family_name' => $passenger->last_name,
                'born_on' => optional($passenger->date_of_birth)->format('Y-m-d'),
                'email' => $booking->contact_email,
                'phone_number' => $booking->e164Phone(),
            ];

            $payload[] = $row;
        }

        $adultIndexes = [];
        $infantIndexes = [];

        foreach ($booking->passengers as $index => $passenger) {
            $type = PassengerMix::normaliseType($passenger->type ?: data_get($offer, 'passengers.'.$index.'.type', 'adult'));
            if ($type === PassengerMix::INFANT) {
                $infantIndexes[] = $index;
            } elseif ($type === PassengerMix::ADULT) {
                $adultIndexes[] = $index;
            }
        }

        foreach ($infantIndexes as $offset => $infantIndex) {
            $adultIndex = $adultIndexes[$offset] ?? $adultIndexes[0] ?? null;
            if ($adultIndex === null) {
                continue;
            }
            $payload[$adultIndex]['infant_passenger_id'] = $payload[$infantIndex]['id'];
        }

        return $payload;
    }
}
