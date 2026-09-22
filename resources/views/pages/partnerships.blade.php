@extends('layouts.app')

@section('title', 'Partnerships')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Partnerships',
    'title' => 'Built for Travel<br>Professionals.',
    'subtitle' => 'Falcore isn’t only about travellers. We understand the needs of the travel industry itself.',
])

<section class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <p class="text-lg text-slate-600 leading-relaxed max-w-3xl">
        We work to support travel agencies, corporate partners, and other organizations that require reliable travel expertise and operational support.
    </p>

    <div class="mt-12 grid md:grid-cols-3 gap-6">
        @foreach([
            ['Travel agencies', 'Booking support, inventory access, and operational backup for agencies.'],
            ['Corporate partners', 'Coordinated programs for teams that travel often and need consistency.'],
            ['Industry organizations', 'Distribution, ticketing, and travel-ecosystem expertise at scale.'],
        ] as $item)
            <article class="rounded-3xl border border-slate-100 p-7">
                <h2 class="font-semibold text-lg">{{ $item[0] }}</h2>
                <p class="mt-3 text-sm text-slate-500">{{ $item[1] }}</p>
            </article>
        @endforeach
    </div>

    <div class="mt-12 rounded-3xl bg-navy-900 text-white px-8 py-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="font-display text-3xl font-bold">Partner With Falcore</h2>
            <p class="mt-2 text-white/70">Tell us how you work. We’ll follow up with the right team.</p>
        </div>
        <a href="{{ route('contact') }}" class="inline-flex rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-3">Start a conversation</a>
    </div>
</section>
@endsection
