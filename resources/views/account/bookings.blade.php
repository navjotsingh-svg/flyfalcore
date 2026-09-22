@extends('layouts.app')

@section('title', 'My trips')

@section('content')
<section class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-gold-600">Account</p>
            <h1 class="mt-2 font-display text-4xl font-bold">My trips</h1>
            <p class="mt-2 text-slate-500">Bookings saved to {{ auth()->user()->email }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('account.passengers') }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Saved passengers</a>
            <a href="{{ route('account.profile') }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Profile</a>
            <a href="{{ route('flights.index') }}" class="rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 px-4 py-2 text-sm font-semibold">Search flights</a>
        </div>
    </div>

    <div class="mt-8 space-y-4">
        @forelse($bookings as $booking)
            <article class="rounded-2xl border border-slate-100 bg-white p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-400">{{ $booking->booking_reference }}</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ $booking->routeLabel() }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $booking->airlineName() }}
                        · {{ optional($booking->created_at)->format('M j, Y') }}
                        · <span class="capitalize">{{ str_replace('_', ' ', $booking->status) }}</span>
                        · <span class="capitalize">{{ $booking->payment_status }}</span>
                    </p>
                </div>
                <div class="md:text-right">
                    <p class="font-semibold">{{ $booking->formattedTotal() }}</p>
                    <a href="{{ route('bookings.show', $booking->booking_reference) }}" class="inline-flex mt-2 text-sm font-semibold text-gold-600 hover:text-gold-500">View booking →</a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-14 text-center">
                <p class="font-semibold">No trips yet</p>
                <p class="mt-2 text-sm text-slate-500">Search a flight to start a booking. Guest trips using this email are added when you sign in.</p>
                <a href="{{ route('flights.index') }}" class="inline-flex mt-5 rounded-full bg-gold-500 text-navy-950 font-semibold px-5 py-2.5">Find a flight</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $bookings->links() }}</div>
</section>
@endsection
