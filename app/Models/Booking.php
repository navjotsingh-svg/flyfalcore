<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'booking_reference',
        'source',
        'user_id',
        'flight_id',
        'duffel_offer_id',
        'duffel_offer_request_id',
        'duffel_order_id',
        'duffel_booking_reference',
        'paypal_order_id',
        'paypal_capture_id',
        'itinerary',
        'payment_payload',
        'fulfillment_error',
        'contact_name',
        'contact_email',
        'contact_phone',
        'passengers_count',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'booked_at',
        'confirmation_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'booked_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'itinerary' => 'array',
            'payment_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'FL'.strtoupper(Str::random(6));
        } while (static::where('booking_reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function formattedTotal(): string
    {
        return $this->formatMoney((float) $this->total_amount, $this->currency ?: 'INR');
    }

    public function formatMoney(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'INR' => '₹',
            'AED' => 'AED ',
        ];

        $symbol = $symbols[strtoupper($currency)] ?? strtoupper($currency).' ';

        return $symbol.number_format($amount, 2);
    }

    public function paypalCurrency(): string
    {
        $currency = strtoupper($this->currency ?: 'INR');
        $supported = ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY', 'INR'];
        $preferred = strtoupper((string) config('services.paypal.currency', ''));

        if ($preferred !== '' && in_array($preferred, $supported, true)) {
            return $preferred;
        }

        return in_array($currency, $supported, true) ? $currency : 'INR';
    }

    public function paypalAmount(): string
    {
        $amount = (float) $this->total_amount;
        $bookingCurrency = strtoupper($this->currency ?: 'INR');
        $paypalCurrency = $this->paypalCurrency();

        if ($bookingCurrency === 'INR' && $paypalCurrency === 'USD') {
            $rate = max(1.0, (float) config('services.fx.inr_per_usd', 83));
            $amount = round($amount / $rate, 2);
        }

        if ($paypalCurrency === 'JPY') {
            return (string) (int) round($amount);
        }

        return number_format($amount, 2, '.', '');
    }

    public function e164Phone(): string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $this->contact_phone);

        if (blank($phone)) {
            return '+919999999999';
        }

        if (! str_starts_with($phone, '+')) {
            $digits = ltrim($phone, '0');
            $phone = strlen($digits) === 10 ? '+91'.$digits : '+'.$digits;
        }

        return $phone;
    }

    public function airlinePnr(): ?string
    {
        return filled($this->duffel_booking_reference) ? (string) $this->duffel_booking_reference : null;
    }

    public function airlineBookingId(): ?string
    {
        return filled($this->duffel_order_id) ? (string) $this->duffel_order_id : null;
    }

    public function grantAccess(): void
    {
        session(['booking_access.'.$this->booking_reference => true]);
    }

    public function accessGranted(): bool
    {
        if (auth()->check()) {
            if ((int) auth()->id() === (int) $this->user_id) {
                return true;
            }

            if (auth()->user()?->is_admin) {
                return true;
            }
        }

        return (bool) session('booking_access.'.$this->booking_reference);
    }

    public function airlineName(): string
    {
        return $this->itinerary['airline'] ?? $this->flight?->airline?->name ?? 'Flight';
    }

    public function routeLabel(): string
    {
        $legs = $this->legs();

        if (count($legs) > 1) {
            $origin = $legs[0]['origin_city'] ?? $legs[0]['origin_code'] ?? '';
            $destination = $legs[0]['destination_city'] ?? $legs[0]['destination_code'] ?? '';

            return trim($origin.' ⇄ '.$destination);
        }

        $origin = $this->itinerary['origin_city'] ?? $this->flight?->originAirport?->city;
        $destination = $this->itinerary['destination_city'] ?? $this->flight?->destinationAirport?->city;

        if ($origin && $destination) {
            return $origin.' → '.$destination;
        }

        return 'Flight booking';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function legs(): array
    {
        $legs = $this->itinerary['legs'] ?? [];

        if (is_array($legs) && $legs !== []) {
            return array_values($legs);
        }

        return [[
            'label' => 'Outbound',
            'airline' => $this->airlineName(),
            'flight_number' => $this->itinerary['flight_number'] ?? $this->flight?->full_flight_number,
            'origin_code' => $this->itinerary['origin_code'] ?? $this->flight?->originAirport?->code,
            'origin_city' => $this->itinerary['origin_city'] ?? $this->flight?->originAirport?->city,
            'origin_name' => $this->itinerary['origin_name'] ?? $this->flight?->originAirport?->name,
            'destination_code' => $this->itinerary['destination_code'] ?? $this->flight?->destinationAirport?->code,
            'destination_city' => $this->itinerary['destination_city'] ?? $this->flight?->destinationAirport?->city,
            'destination_name' => $this->itinerary['destination_name'] ?? $this->flight?->destinationAirport?->name,
            'departure_at' => $this->itinerary['departure_at'] ?? optional($this->flight?->departure_at)->toIso8601String(),
            'arrival_at' => $this->itinerary['arrival_at'] ?? optional($this->flight?->arrival_at)->toIso8601String(),
        ]];
    }

    public function cabinLabel(): string
    {
        return \App\Support\AirlineCopy::cabinLabel(
            $this->itinerary['cabin_class'] ?? $this->flight?->cabin_class ?? 'economy'
        );
    }

    public function extraLines(): array
    {
        return $this->itinerary['extras']['lines'] ?? [];
    }
}
