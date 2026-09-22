<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
    protected $fillable = [
        'booking_id',
        'title',
        'type',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'passport_number',
        'nationality',
        'seat_number',
        'extra_bags',
        'duffel_passenger_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'extra_bags' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function duffelGender(): string
    {
        return match ($this->gender) {
            'female' => 'f',
            default => 'm',
        };
    }

    public function duffelTitle(): string
    {
        if (filled($this->title)) {
            return strtolower($this->title);
        }

        return $this->gender === 'female' ? 'ms' : 'mr';
    }

    public function typeLabel(): string
    {
        return \App\Support\PassengerMix::label($this->type ?: 'adult');
    }
}
