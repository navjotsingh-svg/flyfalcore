<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Passenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PassengerController extends Controller
{
    public function index(Request $request): View
    {
        $passengers = Passenger::query()
            ->with('booking')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('passport_number', 'like', "%{$search}%")
                        ->orWhereHas('booking', fn ($q) => $q->where('booking_reference', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.passengers.index', compact('passengers'));
    }

    public function create(Request $request): View
    {
        return view('admin.passengers.form', [
            'passenger' => new Passenger(['booking_id' => $request->integer('booking_id') ?: null]),
            'bookings' => Booking::query()->latest()->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $passenger = Passenger::create($this->validated($request));

        return redirect()->route('admin.bookings.show', $passenger->booking_id)->with('success', 'Passenger added.');
    }

    public function edit(Passenger $passenger): View
    {
        return view('admin.passengers.form', [
            'passenger' => $passenger,
            'bookings' => Booking::query()->latest()->limit(200)->get(),
        ]);
    }

    public function update(Request $request, Passenger $passenger): RedirectResponse
    {
        $passenger->update($this->validated($request));

        return redirect()->route('admin.bookings.show', $passenger->booking_id)->with('success', 'Passenger updated.');
    }

    public function destroy(Passenger $passenger): RedirectResponse
    {
        $bookingId = $passenger->booking_id;
        $passenger->delete();

        return redirect()->route('admin.bookings.show', $bookingId)->with('success', 'Passenger deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'title' => ['nullable', 'in:mr,mrs,ms,miss,dr'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'type' => ['nullable', 'in:adult,child,infant_without_seat'],
            'passport_number' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'seat_number' => ['nullable', 'string', 'max:10'],
            'duffel_passenger_id' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
