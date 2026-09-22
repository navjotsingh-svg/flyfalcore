@extends('layouts.app')

@section('title', $flight->full_flight_number)

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('flights.index') }}" class="text-sm text-skyline-600 hover:text-skyline-500">← Back to search</a>

    <div class="mt-4 rounded-2xl border border-sand-200 bg-white p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-skyline-700/70">{{ $flight->airline->name }} · {{ $flight->full_flight_number }}</p>
                <h1 class="font-display text-3xl font-semibold mt-1">
                    {{ $flight->originAirport->city }} to {{ $flight->destinationAirport->city }}
                </h1>
            </div>
            <span class="rounded-full bg-sand-100 px-3 py-1 text-sm uppercase tracking-wide">{{ $flight->cabin_class }}</span>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-6">
            <div>
                <p class="text-xs uppercase tracking-wide text-skyline-700/60">Departure</p>
                <p class="text-2xl font-semibold mt-1">{{ $flight->departure_at->format('H:i') }}</p>
                <p class="text-sm text-skyline-700/70">{{ $flight->departure_at->format('D, M j, Y') }}</p>
                <p class="mt-2 font-medium">{{ $flight->originAirport->name }}</p>
                <p class="text-sm text-skyline-700/60">{{ $flight->originAirport->code }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-skyline-700/60">Arrival</p>
                <p class="text-2xl font-semibold mt-1">{{ $flight->arrival_at->format('H:i') }}</p>
                <p class="text-sm text-skyline-700/70">{{ $flight->arrival_at->format('D, M j, Y') }}</p>
                <p class="mt-2 font-medium">{{ $flight->destinationAirport->name }}</p>
                <p class="text-sm text-skyline-700/60">{{ $flight->destinationAirport->code }}</p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-sand-200 pt-6">
            <div>
                <p class="text-sm text-skyline-700/70">Duration {{ $flight->formatted_duration }} · {{ $flight->available_seats }} seats left</p>
                <p class="text-3xl font-semibold text-skyline-600 mt-1">₹{{ number_format($flight->price, 0) }}</p>
            </div>
            <a href="{{ route('bookings.create', $flight) }}"
               class="rounded-full bg-brand-500 hover:bg-brand-600 text-white font-semibold px-6 py-3 transition">
                Continue to book
            </a>
        </div>
    </div>
</section>
@endsection
