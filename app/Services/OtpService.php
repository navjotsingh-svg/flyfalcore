<?php

namespace App\Services;

use App\Mail\OneTimePasscodeMail;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const SESSION_KEY = 'otp.pending';

    public function issue(string $purpose, string $email, array $payload = []): Otp
    {
        $email = strtolower(trim($email));
        $this->guardSendRate($email);

        Otp::query()
            ->where('purpose', $purpose)
            ->where('email', $email)
            ->whereNull('verified_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = Otp::create([
            'purpose' => $purpose,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'payload' => $payload,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => request()->ip(),
        ]);

        Mail::to($email)->send(new OneTimePasscodeMail($code, $purpose, $email));

        $pending = $this->allPending();
        $pending[$purpose] = [
            'purpose' => $purpose,
            'email' => $email,
            'id' => $otp->id,
        ];
        session([self::SESSION_KEY => $pending]);

        return $otp;
    }

    public function resend(string $purpose): Otp
    {
        $pending = $this->pending($purpose);

        if ($pending === null) {
            throw ValidationException::withMessages([
                'code' => 'Request a new verification code from the form first.',
            ]);
        }

        $otp = Otp::query()->find($pending['id']);
        $payload = is_array($otp?->payload) ? $otp->payload : [];

        return $this->issue($pending['purpose'], $pending['email'], $payload);
    }

    public function verify(string $code, string $purpose): array
    {
        $pending = $this->pending($purpose);

        if ($pending === null) {
            throw ValidationException::withMessages([
                'code' => 'Request a verification code first.',
            ]);
        }

        $otp = Otp::query()
            ->whereKey($pending['id'])
            ->where('purpose', $pending['purpose'])
            ->where('email', $pending['email'])
            ->whereNull('verified_at')
            ->first();

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => 'That code has expired. Send a new one.',
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Send a new code.',
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'That verification code is not correct.',
            ]);
        }

        $otp->update(['verified_at' => now()]);
        $this->forgetPurpose($purpose);

        return [
            'purpose' => $otp->purpose,
            'email' => $otp->email,
            'payload' => $otp->payload ?? [],
        ];
    }

    public function pending(?string $purpose = null): ?array
    {
        $pending = $this->allPending();

        if ($purpose) {
            $entry = $pending[$purpose] ?? null;

            return is_array($entry) && filled($entry['email'] ?? null) ? $entry : null;
        }

        foreach ($pending as $entry) {
            if (is_array($entry) && filled($entry['email'] ?? null)) {
                return $entry;
            }
        }

        return null;
    }

    public function clear(?string $purpose = null): void
    {
        if ($purpose) {
            $this->forgetPurpose($purpose);

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    protected function allPending(): array
    {
        $pending = session(self::SESSION_KEY);

        return is_array($pending) ? $pending : [];
    }

    protected function forgetPurpose(string $purpose): void
    {
        $pending = $this->allPending();
        unset($pending[$purpose]);

        if ($pending === []) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session([self::SESSION_KEY => $pending]);
    }

    protected function guardSendRate(string $email): void
    {
        $key = 'otp-send:'.sha1($email.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait a few minutes before requesting another code.',
            ]);
        }

        RateLimiter::hit($key, 15 * 60);
    }
}
