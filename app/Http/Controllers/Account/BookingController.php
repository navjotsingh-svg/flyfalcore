<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = auth()->user()
            ->bookings()
            ->with(['flight.airline', 'passengers'])
            ->latest()
            ->paginate(15);

        return view('account.bookings', compact('bookings'));
    }
}
