<?php

namespace App\Services\Bookings;

use App\Exceptions\PayPalException;
use App\Exceptions\TboException;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Services\PayPal\PayPalClient;
use App\Services\Tbo\TboClient;
use App\Support\AirlineCopy;
use App\Support\PassengerMix;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingCheckoutService
{
    public function __construct(
        protected PayPalClient $paypal,
        protected TboClient $tbo,
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

        if ($booking->source === 'tbo' && blank($booking->airlinePnr())) {
            $this->createTboOrder($booking);
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

    public function createTboOrder(Booking $booking): Booking
    {
        $traceId = (string) data_get($booking->itinerary, 'tbo.trace_id');
        $resultIndex = (string) data_get($booking->itinerary, 'tbo.result_index');
        $returnIndex = (string) data_get($booking->itinerary, 'tbo.return_result_index');
        $isLcc = (bool) data_get($booking->itinerary, 'tbo.is_lcc', false);

        if ($traceId === '' || $resultIndex === '') {
            throw new TboException('This booking has no airline fare to confirm.');
        }

        $booking->loadMissing('passengers');

        $quoted = $this->tbo->quotedItinerary($traceId, $resultIndex);
        $resultIndex = (string) ($quoted['ResultIndex'] ?? $resultIndex);
        $isLcc = (bool) ($quoted['IsLCC'] ?? $isLcc);

        if ($returnIndex !== '') {
            $returnQuoted = $this->tbo->quotedItinerary($traceId, $returnIndex);
            $returnIndex = (string) ($returnQuoted['ResultIndex'] ?? $returnIndex);
        }

        $passengers = $this->tboPassengers($booking, $quoted);

        $payload = [
            'TraceId' => $traceId,
            'ResultIndex' => $resultIndex,
            'Passengers' => $passengers,
            'PreferredCurrency' => 'INR',
        ];

        if ($returnIndex !== '') {
            $payload['ReturnResultIndex'] = $returnIndex;
        }

        $booked = [];

        try {
            if ($isLcc) {
                $order = $this->tbo->ticket($payload);
            } else {
                $booked = $this->tbo->book($payload);
                $pnr = $this->tboPnr($booked);
                $bookingId = $this->tboBookingId($booked);
                $order = $this->tbo->ticket(array_filter([
                    'TraceId' => $traceId,
                    'PNR' => $pnr,
                    'BookingId' => $bookingId,
                    'Passengers' => $passengers,
                    'PreferredCurrency' => 'INR',
                ]));
            }
        } catch (TboException $exception) {
            $booking->update([
                'status' => 'fulfillment_failed',
                'fulfillment_error' => AirlineCopy::publicMessage($exception->getMessage()),
            ]);

            Log::error('TBO ticket failed after PayPal capture', [
                'booking' => $booking->booking_reference,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $pnr = $this->tboPnr($order) ?: $this->tboPnr($booked);
        $bookingId = $this->tboBookingId($order) ?: $this->tboBookingId($booked);
        $details = [];

        if (filled($bookingId)) {
            try {
                $details = $this->tbo->bookingDetails((string) $bookingId, $pnr);
            } catch (TboException $exception) {
                Log::info('TBO booking details unavailable after ticket', [
                    'booking' => $booking->booking_reference,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $booking->update([
            'status' => 'confirmed',
            'booked_at' => now(),
            'duffel_order_id' => $bookingId,
            'duffel_booking_reference' => $pnr,
            'currency' => 'INR',
            'itinerary' => array_merge($booking->itinerary ?? [], [
                'airline_order' => [
                    'id' => $bookingId,
                    'booking_reference' => $pnr,
                    'details' => $details,
                ],
            ]),
            'fulfillment_error' => null,
        ]);

        $this->sendConfirmation($booking->fresh(['passengers', 'flight.airline', 'flight.originAirport', 'flight.destinationAirport']));

        return $booking;
    }

    public function cancelTboBooking(Booking $booking): Booking
    {
        if ($booking->source !== 'tbo') {
            throw new TboException('Only live airline bookings can be cancelled with the airline.');
        }

        if ($booking->payment_status !== 'paid' || ! in_array($booking->status, ['confirmed', 'fulfillment_failed'], true)) {
            throw new TboException('This booking cannot be cancelled right now.');
        }

        $bookingId = $booking->airlineBookingId();

        if (blank($bookingId)) {
            throw new TboException('Missing airline booking id for cancellation.');
        }

        try {
            $response = $this->tbo->cancel((string) $bookingId);
        } catch (TboException $exception) {
            Log::error('TBO cancel failed', [
                'booking' => $booking->booking_reference,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $booking->update([
            'status' => 'cancelled',
            'itinerary' => array_merge($booking->itinerary ?? [], [
                'airline_cancel' => [
                    'at' => now()->toIso8601String(),
                    'response' => $response,
                ],
            ]),
            'fulfillment_error' => null,
        ]);

        return $booking->fresh(['passengers', 'flight.airline', 'flight.originAirport', 'flight.destinationAirport']);
    }

    /**
     * @param  array<string, mixed>  $quoted
     * @return list<array<string, mixed>>
     */
    protected function tboPassengers(Booking $booking, array $quoted = []): array
    {
        $rows = [];
        $leadSet = false;
        $phone = $this->contactPhoneDigits($booking);
        $country = $this->guessCountry($booking);
        $address = $this->contactAddress($booking, $country);

        foreach ($booking->passengers as $index => $passenger) {
            $type = PassengerMix::normaliseType($passenger->type);
            $paxType = match ($type) {
                PassengerMix::CHILD => 2,
                PassengerMix::INFANT => 3,
                default => 1,
            };
            $isLead = ! $leadSet && $paxType === 1;
            if ($isLead) {
                $leadSet = true;
            }

            $title = ucfirst((string) ($passenger->title ?: 'mr'));
            $nationality = strtoupper(substr((string) ($passenger->nationality ?: $country['code']), 0, 2)) ?: 'IN';

            $row = [
                'Title' => $title === 'Dr' ? 'Mr' : $title,
                'FirstName' => $passenger->first_name,
                'LastName' => $passenger->last_name,
                'PaxType' => $paxType,
                'DateOfBirth' => optional($passenger->date_of_birth)->format('Y-m-d\T00:00:00'),
                'Gender' => $passenger->gender === 'female' ? 2 : 1,
                'PassportNo' => $passenger->passport_number,
                'PassportExpiry' => data_get($booking->itinerary, "extras.passports.$index.expiry"),
                'Nationality' => $nationality,
                'CountryCode' => $country['code'],
                'CountryName' => $country['name'],
                'ContactNo' => $phone['number'],
                'CellCountryCode' => $phone['dial'],
                'Email' => $booking->contact_email,
                'IsLeadPax' => $isLead,
                'AddressLine1' => $address['line1'],
                'City' => $address['city'],
            ];

            if (filled($passenger->seat_number) && $paxType !== 3) {
                $seatDynamic = $this->seatDynamicForPassenger($booking, $passenger->seat_number, $quoted);
                if ($seatDynamic !== []) {
                    $row['SeatDynamic'] = $seatDynamic;
                }
            }

            if ((int) $passenger->extra_bags > 0 && $paxType !== 3) {
                $ssr = $this->baggageSsrForPassenger($booking, (int) $passenger->extra_bags, $index, $quoted);
                if ($ssr !== []) {
                    $row['SSR'] = $ssr;
                }
            }

            $rows[] = array_filter($row, fn ($value) => $value !== null && $value !== '');
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $quoted
     * @return list<array<string, mixed>>
     */
    protected function seatDynamicForPassenger(Booking $booking, string $seatNumber, array $quoted): array
    {
        $seatNumber = strtoupper(trim($seatNumber));
        $stored = data_get($booking->itinerary, 'extras.tbo_seats.'.$seatNumber);

        if (is_array($stored) && $stored !== []) {
            return [$stored];
        }

        $leg = ($booking->legs()[0] ?? []);
        $segment = data_get($quoted, 'Segments.0.0', data_get($quoted, 'Segments.0', []));
        $airline = data_get($segment, 'Airline', []);

        return [[
            'AirlineCode' => (string) ($airline['AirlineCode'] ?? $leg['airline_code'] ?? ''),
            'FlightNumber' => (string) ($airline['FlightNumber'] ?? preg_replace('/^[A-Z]{2}/', '', (string) ($leg['flight_number'] ?? ''))),
            'CraftType' => null,
            'Origin' => (string) (data_get($segment, 'Origin.Airport.AirportCode') ?? $leg['origin_code'] ?? ''),
            'Destination' => (string) (data_get($segment, 'Destination.Airport.AirportCode') ?? $leg['destination_code'] ?? ''),
            'DepartureTime' => (string) (data_get($segment, 'Origin.DepTime') ?? $leg['departure_at'] ?? ''),
            'ArrivalTime' => (string) (data_get($segment, 'Destination.ArrTime') ?? $leg['arrival_at'] ?? ''),
            'SeatNumber' => $seatNumber,
            'Code' => $seatNumber,
            'Description' => $seatNumber,
            'Currency' => strtoupper((string) ($booking->currency ?: 'INR')),
        ]];
    }

    /**
     * @param  array<string, mixed>  $quoted
     * @return list<array<string, mixed>>
     */
    protected function baggageSsrForPassenger(Booking $booking, int $qty, int $index, array $quoted): array
    {
        $options = data_get($booking->itinerary, 'extras.tbo_bags.'.$index, []);
        $rows = [];

        if (is_array($options) && $options !== []) {
            foreach (array_slice($options, 0, $qty) as $option) {
                if (! is_array($option)) {
                    continue;
                }
                $rows[] = array_filter([
                    'SSRCode' => $option['code'] ?? $option['SSRCode'] ?? 'XBAG',
                    'SSRType' => (int) ($option['type'] ?? $option['SSRType'] ?? 1),
                    'Quantity' => 1,
                    'Price' => $option['price'] ?? $option['Price'] ?? null,
                    'Currency' => $option['currency'] ?? $booking->currency ?: 'INR',
                ], fn ($value) => $value !== null && $value !== '');
            }

            if ($rows !== []) {
                return $rows;
            }
        }

        $amount = (float) collect($booking->extraLines())
            ->first(fn ($line) => ($line['kind'] ?? '') === 'bag' && (int) ($line['passenger_index'] ?? -1) === $index)['amount'] ?? 0;

        for ($i = 0; $i < $qty; $i++) {
            $rows[] = [
                'SSRCode' => 'XBAG',
                'SSRType' => 1,
                'Quantity' => 1,
                'Price' => $qty > 0 ? round($amount / $qty, 2) : 0,
                'Currency' => strtoupper((string) ($booking->currency ?: 'INR')),
            ];
        }

        return $rows;
    }

    /**
     * @return array{number: string, dial: string}
     */
    protected function contactPhoneDigits(Booking $booking): array
    {
        $raw = preg_replace('/[^\d+]/', '', (string) $booking->contact_phone) ?: '';
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
            return ['dial' => '91', 'number' => substr($digits, 2)];
        }

        if (strlen($digits) === 10) {
            return ['dial' => '91', 'number' => $digits];
        }

        if ($digits !== '') {
            return ['dial' => '91', 'number' => ltrim($digits, '0')];
        }

        return ['dial' => '91', 'number' => '9999999999'];
    }

    /**
     * @return array{code: string, name: string}
     */
    protected function guessCountry(Booking $booking): array
    {
        $nationality = strtoupper(substr((string) ($booking->passengers->first()?->nationality ?: 'IN'), 0, 2));

        return match ($nationality) {
            'US' => ['code' => 'US', 'name' => 'United States'],
            'GB', 'UK' => ['code' => 'GB', 'name' => 'United Kingdom'],
            'AE' => ['code' => 'AE', 'name' => 'United Arab Emirates'],
            default => ['code' => 'IN', 'name' => 'India'],
        };
    }

    /**
     * @param  array{code: string, name: string}  $country
     * @return array{line1: string, city: string}
     */
    protected function contactAddress(Booking $booking, array $country): array
    {
        $city = (string) (data_get($booking->itinerary, 'origin_city')
            ?: $booking->flight?->originAirport?->city
            ?: ($country['code'] === 'IN' ? 'Delhi' : 'City'));

        return [
            'line1' => trim((string) $booking->contact_name) !== ''
                ? 'C/O '.trim((string) $booking->contact_name)
                : 'Falcore traveler',
            'city' => $city,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function tboPnr(array $payload): ?string
    {
        $pnr = data_get($payload, 'Response.PNR')
            ?? data_get($payload, 'Response.FlightItinerary.PNR')
            ?? data_get($payload, 'PNR');

        return filled($pnr) ? (string) $pnr : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function tboBookingId(array $payload): ?string
    {
        $id = data_get($payload, 'Response.BookingId')
            ?? data_get($payload, 'Response.FlightItinerary.BookingId')
            ?? data_get($payload, 'BookingId');

        return filled($id) ? (string) $id : null;
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
}
