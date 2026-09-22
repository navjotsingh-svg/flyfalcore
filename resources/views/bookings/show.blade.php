@extends('layouts.app')

@section('title', 'Booking '.$booking->booking_reference)

@section('content')
@php
    $itinerary = $booking->itinerary ?? [];
    $depart = $itinerary['departure_at'] ?? optional($booking->flight?->departure_at)->toIso8601String();
    $arrive = $itinerary['arrival_at'] ?? optional($booking->flight?->arrival_at)->toIso8601String();
@endphp
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
    @auth
        <p class="mb-4 text-sm"><a href="{{ route('account.bookings') }}" class="font-semibold text-navy-900 hover:text-gold-600">← My trips</a></p>
    @endauth
    @if($booking->payment_status === 'paid' && $booking->status === 'confirmed')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-900">
            <p class="font-semibold">Booking confirmed</p>
            <p class="text-sm mt-1">Save your reference <strong>{{ $booking->booking_reference }}</strong> to manage this trip.</p>
        </div>
    @elseif($booking->status === 'fulfillment_failed')
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900">
            <p class="font-semibold">Payment received — ticket pending</p>
            <p class="text-sm mt-1">PayPal captured the fare, but the airline order still needs to be issued. {{ $booking->fulfillment_error }}</p>
            @if($booking->payment_status === 'paid' && $booking->source === 'duffel' && blank($booking->duffel_order_id))
                <form action="{{ route('bookings.issue', $booking->booking_reference) }}" method="POST" class="mt-3">
                    @csrf
                    <button class="inline-flex rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-5 py-2.5">Issue ticket</button>
                </form>
            @endif
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
            <p class="font-semibold">Awaiting payment</p>
            <p class="text-sm mt-1 text-slate-500">Complete payment to confirm this fare.</p>
            @if($booking->payment_status !== 'paid')
                <a href="{{ route('paypal.retry', $booking->booking_reference) }}" class="inline-flex mt-3 rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-5 py-2.5">Pay Now</a>
            @endif
        </div>
    @endif

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 space-y-6">
        <div class="flex flex-wrap justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Falcore reference</p>
                <p class="text-3xl font-extrabold tracking-wide">{{ $booking->booking_reference }}</p>
            </div>
            <div class="text-right text-sm">
                <p class="capitalize">Status: <strong>{{ str_replace('_', ' ', $booking->status) }}</strong></p>
                <p class="capitalize text-slate-500">Payment: {{ $booking->payment_status }}</p>
                @if($booking->source === 'duffel')
                    <p class="mt-1 text-xs font-semibold text-brand-600">Live airline fare</p>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <p class="text-sm text-slate-500">{{ $booking->airlineName() }} · {{ $itinerary['flight_number'] ?? $booking->flight?->full_flight_number }} · {{ $booking->cabinLabel() }}</p>
            <h2 class="text-2xl font-extrabold mt-1">{{ $booking->routeLabel() }}</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-400 uppercase text-xs tracking-wide">Depart</p>
                    @if($depart)
                        <p class="font-semibold text-lg">{{ \Carbon\Carbon::parse($depart)->format('H:i') }}</p>
                        <p>{{ \Carbon\Carbon::parse($depart)->format('D, M j, Y') }}</p>
                    @endif
                    <p class="mt-1">{{ $itinerary['origin_name'] ?? $booking->flight?->originAirport?->name }} ({{ $itinerary['origin_code'] ?? $booking->flight?->originAirport?->code }})</p>
                </div>
                <div>
                    <p class="text-slate-400 uppercase text-xs tracking-wide">Arrive</p>
                    @if($arrive)
                        <p class="font-semibold text-lg">{{ \Carbon\Carbon::parse($arrive)->format('H:i') }}</p>
                        <p>{{ \Carbon\Carbon::parse($arrive)->format('D, M j, Y') }}</p>
                    @endif
                    <p class="mt-1">{{ $itinerary['destination_name'] ?? $booking->flight?->destinationAirport?->name }} ({{ $itinerary['destination_code'] ?? $booking->flight?->destinationAirport?->code }})</p>
                </div>
            </div>
        </div>

        @if($booking->duffel_booking_reference)
            <div class="border-t border-slate-100 pt-6 text-sm">
                <p>Airline PNR: <strong>{{ $booking->duffel_booking_reference }}</strong></p>
            </div>
        @endif

        <div class="border-t border-slate-100 pt-6">
            <h3 class="font-semibold">Passengers</h3>
            <ul class="mt-3 space-y-2">
                @foreach($booking->passengers as $passenger)
                    <li class="flex justify-between text-sm border-b border-slate-100 pb-2">
                        <span>
                            {{ $passenger->full_name }} · {{ $passenger->typeLabel() }}
                            @if($passenger->seat_number)
                                · Seat {{ $passenger->seat_number }}
                            @endif
                            @if($passenger->extra_bags)
                                · Extra bag × {{ $passenger->extra_bags }}
                            @endif
                        </span>
                        <span class="text-slate-400">{{ $passenger->passport_number ?: '—' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($booking->extraLines())
            <div class="border-t border-slate-100 pt-6">
                <h3 class="font-semibold">Add-ons</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($booking->extraLines() as $line)
                        <li class="flex justify-between">
                            <span>{{ $line['label'] ?? 'Add-on' }}</span>
                            <span>{{ !empty($line['amount']) ? $booking->formatMoney((float) $line['amount'], $booking->currency) : 'Included' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border-t border-slate-100 pt-6 flex justify-between items-center">
            <div>
                <p class="text-sm text-slate-500">Contact</p>
                <p>{{ $booking->contact_name }}</p>
                <p class="text-sm text-slate-500">{{ $booking->contact_email }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">{{ $booking->payment_status === 'paid' ? 'Total paid' : 'Amount due' }}</p>
                <p class="text-2xl font-semibold text-brand-600">{{ $booking->formattedTotal() }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
