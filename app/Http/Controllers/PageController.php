<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function hotels(): View
    {
        return view('pages.hotels');
    }

    public function cars(): View
    {
        return view('pages.cars');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function privacy(): View
    {
        return view('pages.legal', [
            'title' => 'Privacy Policy',
            'eyebrow' => 'Privacy',
            'intro' => 'How Falcore collects, uses, and protects traveler information.',
            'sections' => [
                ['What we collect', 'Account details, booking contact information, passenger names, and payment references needed to complete your trip.'],
                ['How we use it', 'We use this information to search fares, process PayPal payments, issue airline tickets through TBO, and send booking confirmations.'],
                ['Sharing', 'We share only what is required with payment and airline partners (PayPal and TBO) to fulfill your booking.'],
                ['Retention', 'Booking records are kept for travel operations and legal requirements. You can ask us to update or delete account data where allowed.'],
            ],
        ]);
    }

    public function terms(): View
    {
        return view('pages.legal', [
            'title' => 'Terms & Conditions',
            'eyebrow' => 'Terms',
            'intro' => 'Rules that apply when you search and book with Falcore.',
            'sections' => [
                ['Fares', 'Live fares come from airline inventory via TBO and can change until payment is completed and the ticket is issued.'],
                ['Payments', 'Payment is collected through PayPal. A paid booking that fails airline ticketing remains pending until the ticket is issued or cancelled.'],
                ['Cancellations', 'Airline cancellation rules apply. Falcore can request cancellation with the airline for eligible live bookings; refunds follow airline and PayPal policies.'],
                ['Accuracy', 'You are responsible for passenger names, dates of birth, and travel documents matching airline requirements.'],
            ],
        ]);
    }

    public function cookies(): View
    {
        return view('pages.legal', [
            'title' => 'Cookie Policy',
            'eyebrow' => 'Cookies',
            'intro' => 'How Falcore uses cookies and similar storage in your browser.',
            'sections' => [
                ['Essential', 'We use session cookies so you can stay signed in, complete checkout, and reopen a verified booking.'],
                ['Preferences', 'We may remember recent search choices such as trip type to make the next visit faster.'],
                ['Analytics', 'If analytics tools are enabled later, they will only measure site usage in aggregate.'],
                ['Control', 'You can clear cookies in your browser at any time. Essential cookies are required for booking flows to work.'],
            ],
        ]);
    }

    public function services(): View
    {
        return view('pages.services');
    }

    public function corporate(): View
    {
        return view('pages.corporate');
    }

    public function partnerships(): View
    {
        return view('pages.partnerships');
    }

    public function travelers(): View
    {
        return view('pages.travelers');
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'pendingOtp' => $this->otp->pending(Otp::CONTACT),
        ]);
    }

    public function destinations(): View
    {
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('city')
            ->get();

        return view('pages.destinations', compact('airports'));
    }

    public function signup(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('account.bookings');
        }

        return view('pages.signup', [
            'pendingOtp' => $this->otp->pending(Otp::SIGNUP),
        ]);
    }

    public function storeSignup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->otp->issue(Otp::SIGNUP, $validated['email'], [
            'name' => $validated['name'],
        ]);
        session(['otp.secret' => $validated['password']]);

        return redirect()
            ->route('signup')
            ->with('success', 'We sent a 6-digit code to '.$validated['email'].'. Enter it to finish creating your account.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->otp->issue(Otp::CONTACT, $validated['email'], $validated);

        return redirect()
            ->route('contact')
            ->with('success', 'We sent a 6-digit code to '.$validated['email'].'. Enter it to save your message.');
    }

    public function storeNewsletter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:120'],
        ]);

        $this->otp->issue(Otp::NEWSLETTER, $validated['email'], $validated);

        return back()->with('success', 'We sent a 6-digit code to '.$validated['email'].'. Enter it to join the list.');
    }
}
