@extends('layouts.app')

@section('title', 'Find Booking')

@section('content')
<section class="max-w-md mx-auto px-4 sm:px-6 py-14">
    <h1 class="font-display text-3xl font-semibold text-center">Find your booking</h1>
    <p class="mt-2 text-center text-skyline-700/70">Enter your reference and email to view trip details.</p>

    <form action="{{ route('bookings.find') }}" method="POST" class="mt-8 rounded-2xl border border-sand-200 bg-white p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Booking reference</label>
            <input type="text" name="booking_reference" value="{{ old('booking_reference') }}" required placeholder="e.g. FLABC123"
                   class="w-full rounded-lg border border-sand-200 px-3 py-2.5 bg-sand-50 uppercase">
            @error('booking_reference') <p class="text-coral-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="contact_email" value="{{ old('contact_email') }}" required
                   class="w-full rounded-lg border border-sand-200 px-3 py-2.5 bg-sand-50">
            @error('contact_email') <p class="text-coral-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded-full bg-brand-500 hover:bg-brand-600 text-white font-semibold py-2.5 transition">
            Find booking
        </button>
    </form>
</section>
@endsection
