<?php

namespace App\Support;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateOfBirth implements ValidationRule
{
    public static function normalize(?string $value, ?string $day = null, ?string $month = null, ?string $year = null): ?string
    {
        if (filled($day) && filled($month) && filled($year)) {
            return sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return $value;
    }

    public static function normalizePassengerList(array $passengers): array
    {
        foreach ($passengers as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $passengers[$index]['date_of_birth'] = static::normalize(
                $passenger['date_of_birth'] ?? null,
                $passenger['dob_day'] ?? null,
                $passenger['dob_month'] ?? null,
                $passenger['dob_year'] ?? null,
            );
        }

        return $passengers;
    }

    public static function bookingPayload(array $passenger): array
    {
        $payload = array_filter([
            'title' => $passenger['title'] ?? null,
            'type' => $passenger['type'] ?? null,
            'first_name' => $passenger['first_name'] ?? null,
            'last_name' => $passenger['last_name'] ?? null,
            'date_of_birth' => $passenger['date_of_birth'] ?? null,
            'gender' => $passenger['gender'] ?? null,
            'passport_number' => $passenger['passport_number'] ?? null,
            'nationality' => $passenger['nationality'] ?? null,
            'seat_number' => $passenger['seat_number'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if (array_key_exists('extra_bags', $passenger)) {
            $payload['extra_bags'] = (int) $passenger['extra_bags'];
        }

        return $payload;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $fail('Enter a valid date of birth in DD/MM/YYYY format.');

            return;
        }

        $normalized = is_string($value) ? $value : (string) $value;

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $normalized, $matches)) {
            $fail('Enter a valid date of birth in DD/MM/YYYY format.');

            return;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (! checkdate($month, $day, $year)) {
            $fail('Enter a valid date of birth in DD/MM/YYYY format.');

            return;
        }

        if (Carbon::createFromDate($year, $month, $day)->startOfDay()->gte(now()->startOfDay())) {
            $fail('Date of birth must be before today.');
        }
    }
}
