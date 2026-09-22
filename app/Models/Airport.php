<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airport extends Model
{
    protected $fillable = [
        'name',
        'code',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function departingFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'origin_airport_id');
    }

    public function arrivingFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'destination_airport_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->city} ({$this->code})";
    }
}
