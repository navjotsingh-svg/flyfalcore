@extends('layouts.app')

@section('title', 'Travelers')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Travelers',
    'title' => 'One Travel Ecosystem.<br>Global Reach.',
    'subtitle' => 'Falcore operates within a connected travel ecosystem designed to serve different markets and travel requirements.',
])

<section class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['FALCORE', 'Global Travel Services & Operations'],
            ['TRAVELERS', 'Domestic • International • Leisure'],
            ['BUSINESSES', 'Corporate • Groups • Industry partners'],
        ] as $brand)
            <article class="rounded-3xl bg-navy-900 text-white p-8 min-h-[220px] flex flex-col justify-end">
                <p class="text-gold-400 text-xs font-bold tracking-[0.18em]">{{ $brand[0] }}</p>
                <h2 class="mt-3 font-display text-2xl font-bold">{{ $brand[1] }}</h2>
            </article>
        @endforeach
    </div>
    <p class="mt-10 max-w-3xl text-slate-600 leading-relaxed">
        Falcore provides the travel expertise and operational foundation travellers and businesses need, from leisure trips to corporate programmes, through one Falcore account.
    </p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('flights.index') }}" class="inline-flex rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-3">Search flights</a>
        <a href="{{ auth()->check() ? route('account.bookings') : route('login') }}" class="inline-flex rounded-full border-2 border-navy-900 text-navy-900 font-semibold px-6 py-3">My trips</a>
        <a href="{{ route('bookings.lookup') }}" class="inline-flex rounded-full border-2 border-slate-200 text-slate-600 font-semibold px-6 py-3">Look up a booking</a>
    </div>
</section>
@endsection
