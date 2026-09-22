<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function savedPassengers(): HasMany
    {
        return $this->hasMany(SavedPassenger::class);
    }

    public function checkoutPassengers(): array
    {
        $this->importSavedPassengersFromBookings();

        return $this->savedPassengers()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (SavedPassenger $passenger) => $passenger->checkoutPayload())
            ->values()
            ->all();
    }

    public function rememberPassengers(array $passengers): void
    {
        foreach ($passengers as $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            if (($passenger['save'] ?? '1') === '0' || ($passenger['save'] ?? '1') === 0) {
                continue;
            }

            $firstName = trim((string) ($passenger['first_name'] ?? ''));
            $lastName = trim((string) ($passenger['last_name'] ?? ''));

            if ($firstName === '' || $lastName === '') {
                continue;
            }

            $dob = $passenger['date_of_birth'] ?: null;

            $existing = $this->savedPassengers()
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->when(
                    $dob,
                    fn ($query) => $query->whereDate('date_of_birth', $dob),
                    fn ($query) => $query->whereNull('date_of_birth'),
                )
                ->first();

            $existing ??= $this->savedPassengers()->make([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob,
            ]);

            $existing->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob,
                'title' => $passenger['title'] ?? $existing->title,
                'gender' => $passenger['gender'] ?? $existing->gender,
                'passport_number' => $passenger['passport_number'] ?? $existing->passport_number,
                'nationality' => $passenger['nationality'] ?? $existing->nationality,
            ])->save();
        }
    }

    public function importSavedPassengersFromBookings(): void
    {
        $passengers = Passenger::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', $this->id))
            ->get()
            ->map(fn (Passenger $passenger) => [
                'title' => $passenger->title,
                'first_name' => $passenger->first_name,
                'last_name' => $passenger->last_name,
                'date_of_birth' => optional($passenger->date_of_birth)?->toDateString(),
                'gender' => $passenger->gender,
                'passport_number' => $passenger->passport_number,
                'nationality' => $passenger->nationality,
            ])
            ->all();

        if ($passengers !== []) {
            $this->rememberPassengers($passengers);
        }
    }

    public function claimBookings(): int
    {
        return Booking::query()
            ->whereNull('user_id')
            ->where('contact_email', $this->email)
            ->update(['user_id' => $this->id]);
    }
}
