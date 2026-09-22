@extends('layouts.app')

@section('title', $offer['flight_number'] ?? 'Offer')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('flights.index') }}" class="text-sm text-brand-600">← Back to search</a>
    <div class="mt-4 rounded-[28px] border border-slate-100 bg-white p-6 sm:p-8">
        <p class="text-sm text-slate-500">{{ $offer['airline'] }} · {{ $offer['flight_number'] }} · {{ $offer['cabin_label'] ?? \App\Support\AirlineCopy::cabinLabel($offer['cabin_class'] ?? 'economy') }}</p>
        <h1 class="text-3xl font-extrabold mt-1">{{ $offer['origin_city'] }} to {{ $offer['destination_city'] }}</h1>
        <div class="mt-6 grid grid-cols-2 gap-6">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Departure</p>
                <p class="text-2xl font-semibold mt-1">{{ $offer['departure_at']->format('H:i') }}</p>
                <p class="text-sm text-slate-500">{{ $offer['departure_at']->format('D, M j, Y') }}</p>
                <p class="mt-2 font-medium">{{ $offer['origin_name'] }} ({{ $offer['origin_code'] }})</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Arrival</p>
                <p class="text-2xl font-semibold mt-1">{{ $offer['arrival_at']->format('H:i') }}</p>
                <p class="text-sm text-slate-500">{{ $offer['arrival_at']->format('D, M j, Y') }}</p>
                <p class="mt-2 font-medium">{{ $offer['destination_name'] }} ({{ $offer['destination_code'] }})</p>
            </div>
        </div>
        <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-6">
            <div>
                <p class="text-sm text-slate-500">{{ $offer['formatted_duration'] }} · {{ $offer['stops'] === 0 ? 'Non-stop' : $offer['stops'].' stop(s)' }}</p>
                <p class="text-3xl font-semibold text-brand-600 mt-1">{{ $offer['currency'] }} {{ number_format($offer['price'], 2) }}</p>
            </div>
            <a href="{{ route('offers.book', $offer['id']) }}" class="rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-3">Book Now</a>
        </div>
    </div>
</section>
@endsection
