@extends('layouts.admin')
@section('title', 'Booking '.$booking->booking_reference)
@section('heading', 'Booking '.$booking->booking_reference)
@section('content')
<div class="flex flex-wrap gap-3 mb-5">
    <a href="{{ route('admin.bookings.edit', $booking) }}" class="rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Edit booking</a>
    <a href="{{ route('admin.passengers.create', ['booking_id' => $booking->id]) }}" class="rounded-full border border-slate-200 bg-white font-semibold px-4 py-2 text-sm">Add passenger</a>
    <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="rounded-full border border-slate-200 bg-white font-semibold px-4 py-2 text-sm">Public view</a>
</div>

<div class="grid lg:grid-cols-2 gap-4">
    <div class="rounded-2xl bg-white border border-slate-200 p-5 text-sm space-y-2">
        <h2 class="font-bold text-base">Contact</h2>
        <p>{{ $booking->contact_name }}</p>
        <p class="text-slate-500">{{ $booking->contact_email }} · {{ $booking->contact_phone ?: '—' }}</p>
        <p>Status: <strong class="capitalize">{{ str_replace('_',' ', $booking->status) }}</strong></p>
        <p>Payment: <strong class="capitalize">{{ $booking->payment_status }}</strong> · {{ $booking->formattedTotal() }}</p>
        <p>Source: {{ $booking->source }}</p>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200 p-5 text-sm space-y-2">
        <h2 class="font-bold text-base">Trip</h2>
        <p>{{ $booking->routeLabel() }}</p>
        <p>{{ $booking->airlineName() }}</p>
        @if($booking->flight)
            <p>{{ $booking->flight->full_flight_number }} · {{ $booking->flight->departure_at->format('D, M j H:i') }}</p>
        @endif
        <p>PNR: {{ $booking->duffel_booking_reference ?: '—' }}</p>
        <p>PayPal capture: {{ $booking->paypal_capture_id ?: '—' }}</p>
    </div>
</div>

<div class="mt-6 rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <div class="px-5 py-4 border-b border-slate-100 font-bold">Passengers</div>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Name</th><th class="px-5 py-3">DOB</th><th class="px-5 py-3">Passport</th><th class="px-5 py-3">Seat</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($booking->passengers as $passenger)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3">{{ $passenger->full_name }}</td>
                <td class="px-5 py-3">{{ optional($passenger->date_of_birth)->format('Y-m-d') ?: '—' }}</td>
                <td class="px-5 py-3">{{ $passenger->passport_number ?: '—' }}</td>
                <td class="px-5 py-3">{{ $passenger->seat_number ?: '—' }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.passengers.edit', $passenger) }}">Edit</a>
                    <form action="{{ route('admin.passengers.destroy', $passenger) }}" method="POST" class="inline" onsubmit="return confirm('Delete this passenger?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No passengers on this booking.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
