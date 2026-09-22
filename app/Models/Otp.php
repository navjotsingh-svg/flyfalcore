<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    public const SIGNUP = 'signup';

    public const CONTACT = 'contact';

    public const NEWSLETTER = 'newsletter';

    protected $fillable = [
        'purpose',
        'email',
        'code_hash',
        'payload',
        'attempts',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
