<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Flight;
use App\Models\NewsletterSubscriber;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Bookings', 'value' => Booking::count(), 'href' => route('admin.bookings.index')],
                ['label' => 'Flights', 'value' => Flight::count(), 'href' => route('admin.flights.index')],
                ['label' => 'Passengers', 'value' => Passenger::count(), 'href' => route('admin.passengers.index')],
                ['label' => 'Airlines', 'value' => Airline::count(), 'href' => route('admin.airlines.index')],
                ['label' => 'Airports', 'value' => Airport::count(), 'href' => route('admin.airports.index')],
                ['label' => 'Users', 'value' => User::count(), 'href' => route('admin.users.index')],
                ['label' => 'Messages', 'value' => ContactMessage::count(), 'href' => route('admin.messages.index')],
                ['label' => 'Newsletter', 'value' => NewsletterSubscriber::count(), 'href' => route('admin.subscribers.index')],
            ],
            'recentBookings' => Booking::query()->latest()->take(8)->get(),
            'paidToday' => Booking::query()->where('payment_status', 'paid')->whereDate('updated_at', today())->count(),
            'pendingPayments' => Booking::query()->whereIn('payment_status', ['unpaid', 'pending'])->count(),
        ]);
    }
}
