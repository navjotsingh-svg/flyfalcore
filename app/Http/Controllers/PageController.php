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
