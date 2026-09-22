<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPassenger extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'passport_number',
        'nationality',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function checkoutPayload(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?: 'mr',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => optional($this->date_of_birth)?->format('Y-m-d'),
            'dob_day' => optional($this->date_of_birth)?->format('d'),
            'dob_month' => optional($this->date_of_birth)?->format('m'),
            'dob_year' => optional($this->date_of_birth)?->format('Y'),
            'gender' => $this->gender,
            'passport_number' => $this->passport_number,
            'nationality' => $this->nationality,
            'label' => $this->full_name,
        ];
    }
}
