@extends('layouts.app')

@section('title', 'About Falcore')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'About Falcore',
    'title' => 'At the Core of<br>Modern Travel',
    'subtitle' => 'Travel is an ecosystem. Falcore brings those connections together.',
])

<section class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="grid lg:grid-cols-12 gap-12">
        <div class="lg:col-span-7 space-y-5 text-slate-600 leading-relaxed text-lg">
            <p>Travel is an ecosystem. Behind every successful journey are airlines, travel professionals, technology, distribution systems, payments, customer support, and countless connections working together.</p>
            <p class="text-navy-900 font-semibold">Falcore brings those connections together.</p>
            <p>We provide professional travel services and operational expertise designed to make travel more accessible, efficient, and seamless.</p>
            <p>Our focus extends beyond simply booking a ticket. We build the infrastructure, relationships, and expertise that help journeys happen.</p>
        </div>
        <div class="lg:col-span-5 rounded-3xl bg-navy-900 text-white p-8">
            <p class="text-gold-400 text-xs font-bold tracking-[0.2em]">THE CORE OF EVERY JOURNEY</p>
            <p class="mt-4 font-display text-3xl font-bold">Connect. Simplify. Move.</p>
            <p class="mt-4 text-white/70">That’s Falcore.</p>
        </div>
    </div>

    <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach([
            ['Global Perspective', 'Connecting destinations across the world'],
            ['Industry Expertise', 'Years of experience in travel & aviation'],
            ['Trusted Partnerships', 'Strong relationships across the industry'],
            ['Built for Growth', 'Solutions that scale with your needs'],
        ] as $item)
            <article class="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                <h2 class="font-semibold text-navy-900">{{ $item[0] }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $item[1] }}</p>
            </article>
        @endforeach
    </div>
</section>
@endsection
