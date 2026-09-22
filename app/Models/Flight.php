<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    protected $fillable = [
        'airline_id',
        'flight_number',
        'origin_airport_id',
        'destination_airport_id',
        'departure_at',
        'arrival_at',
        'duration_minutes',
        'price',
        'cabin_class',
        'total_seats',
        'available_seats',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return sprintf('%dh %02dm', $hours, $minutes);
    }

    public function getFullFlightNumberAttribute(): string
    {
        return $this->airline->code.$this->flight_number;
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('available_seats', '>', 0)
            ->where('departure_at', '>', now());
    }

    public function scopeRoute(Builder $query, int $originId, int $destinationId): Builder
    {
        return $query->where('origin_airport_id', $originId)
            ->where('destination_airport_id', $destinationId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('departure_at', $date);
    }
}
