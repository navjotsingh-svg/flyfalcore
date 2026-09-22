<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['flight.airline', 'passengers'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('booking_reference', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhere('duffel_booking_reference', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('payment_status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        return view('admin.bookings.form', [
            'booking' => new Booking(['source' => 'local', 'currency' => 'INR', 'status' => 'pending', 'payment_status' => 'unpaid', 'passengers_count' => 1]),
            'flights' => Flight::query()->with(['airline', 'originAirport', 'destinationAirport'])->orderByDesc('departure_at')->limit(200)->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Booking::create($this->validated($request));

        return redirect()->route('admin.bookings.index')->with('success', 'Booking created.');
    }

    public function show(Booking $booking): View
    {
        $booking->load(['flight.airline', 'flight.originAirport', 'flight.destinationAirport', 'passengers', 'user']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking): View
    {
        return view('admin.bookings.form', [
            'booking' => $booking,
            'flights' => Flight::query()->with(['airline', 'originAirport', 'destinationAirport'])->orderByDesc('departure_at')->limit(200)->get(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $booking->update($this->validated($request, $booking));

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Booking updated.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted.');
    }

    protected function validated(Request $request, ?Booking $booking = null): array
    {
        return $request->validate([
            'source' => ['required', 'in:local,duffel'],
            'user_id' => ['nullable', 'exists:users,id'],
            'flight_id' => ['nullable', 'exists:flights,id'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'passengers_count' => ['required', 'integer', 'min:1', 'max:9'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'in:pending,confirmed,cancelled,completed,fulfillment_failed'],
            'payment_status' => ['required', 'in:unpaid,pending,paid,refunded,failed'],
            'duffel_offer_id' => ['nullable', 'string', 'max:80'],
            'duffel_order_id' => ['nullable', 'string', 'max:80'],
            'duffel_booking_reference' => ['nullable', 'string', 'max:40'],
            'paypal_order_id' => ['nullable', 'string', 'max:80'],
            'paypal_capture_id' => ['nullable', 'string', 'max:80'],
            'fulfillment_error' => ['nullable', 'string'],
            'booked_at' => ['nullable', 'date'],
        ]);
    }
}
