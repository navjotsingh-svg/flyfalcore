<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', Rule::in([Otp::SIGNUP, Otp::CONTACT, Otp::NEWSLETTER])],
            'code' => ['required', 'digits:6'],
        ]);

        $result = $this->otp->verify($validated['code'], $validated['purpose']);

        return match ($result['purpose']) {
            Otp::SIGNUP => $this->completeSignup($request, $result),
            Otp::CONTACT => $this->completeContact($result),
            Otp::NEWSLETTER => $this->completeNewsletter($result),
            default => back()->with('error', 'That verification request is not valid.'),
        };
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', Rule::in([Otp::SIGNUP, Otp::CONTACT, Otp::NEWSLETTER])],
        ]);

        $this->otp->resend($validated['purpose']);

        return back()->with('success', 'A new verification code is on the way.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', Rule::in([Otp::SIGNUP, Otp::CONTACT, Otp::NEWSLETTER])],
        ]);

        $this->otp->clear($validated['purpose']);

        if ($validated['purpose'] === Otp::SIGNUP) {
            session()->forget('otp.secret');
        }

        return back()->with('success', 'Verification cancelled. You can send the form again.');
    }

    protected function completeSignup(Request $request, array $result): RedirectResponse
    {
        $payload = $result['payload'];
        $password = session('otp.secret');
        session()->forget('otp.secret');

        if (blank($password) || blank($payload['name'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => 'Your signup details expired. Please start again.',
            ]);
        }

        if (User::query()->where('email', $result['email'])->exists()) {
            throw ValidationException::withMessages([
                'code' => 'An account already exists for this email. Please sign in.',
            ]);
        }

        $user = User::create([
            'name' => $payload['name'],
            'email' => $result['email'],
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->claimBookings();

        return redirect()
            ->route('account.bookings')
            ->with('success', 'Welcome to Falcore. Your email is verified and your trips will appear here after you book.');
    }

    protected function completeContact(array $result): RedirectResponse
    {
        $payload = $result['payload'];

        ContactMessage::create([
            'name' => $payload['name'] ?? '',
            'email' => $result['email'],
            'phone' => $payload['phone'] ?? null,
            'message' => $payload['message'] ?? '',
        ]);

        return redirect()
            ->route('contact')
            ->with('success', 'Thanks for reaching out. Your message is saved and our travel team will reply shortly.');
    }

    protected function completeNewsletter(array $result): RedirectResponse
    {
        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => $result['email']],
            ['verified_at' => now()],
        );

        return back()->with('success', 'You are on the list. Fresh travel ideas are on the way.');
    }
}
