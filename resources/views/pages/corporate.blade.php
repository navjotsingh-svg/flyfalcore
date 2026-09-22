@extends('layouts.app')

@section('title', 'Corporate Travel')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Corporate Travel',
    'title' => 'Business Travel,<br>Without the Complexity.',
    'subtitle' => 'Falcore provides travel solutions designed around the needs of businesses and professionals.',
])

<section class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <p class="text-lg text-slate-600 leading-relaxed max-w-3xl">
        Business travel demands precision. Flights change. Meetings move. Schedules shift. International travel creates additional complexity.
    </p>
    <p class="mt-4 text-lg text-slate-600 leading-relaxed max-w-3xl">
        Falcore provides travel solutions designed around the needs of businesses and professionals.
    </p>

    <div class="mt-12 grid md:grid-cols-2 gap-8">
        <div class="rounded-3xl bg-navy-900 text-white p-8">
            <h2 class="font-display text-2xl font-bold">What we arrange</h2>
            <ul class="mt-5 space-y-3 text-white/80">
                @foreach(['Corporate flight arrangements', 'International business travel', 'Executive travel', 'Group travel', 'Multi-city itineraries'] as $item)
                    <li class="flex gap-3"><i class="fa-solid fa-check text-gold-400 mt-1"></i>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-slate-50 p-8">
            <h2 class="font-display text-2xl font-bold">How we support teams</h2>
            <ul class="mt-5 space-y-3 text-slate-600">
                @foreach(['Travel coordination', 'Booking assistance', 'Corporate travel support'] as $item)
                    <li class="flex gap-3"><i class="fa-solid fa-check text-gold-600 mt-1"></i>{{ $item }}</li>
                @endforeach
            </ul>
            <a href="{{ route('contact') }}" class="inline-flex mt-8 rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-3">Talk to our team</a>
        </div>
    </div>
</section>
@endsection
