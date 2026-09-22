@extends('layouts.app')

@section('title', 'Home')

@section('content')
<section class="hero-official">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-16 lg:pt-24 pb-10 flex flex-col">
        <div class="order-2 lg:order-1 max-w-xl mt-10 lg:mt-0">
            <h1 class="font-display text-4xl sm:text-6xl lg:text-[68px] font-bold text-white leading-[1.05]">
                The Core of<br>Every Journey
            </h1>
            <h2 class="mt-5 text-lg sm:text-xl font-semibold text-gold-400">
                Connecting People. Powering Travel. Moving the World.
            </h2>
            <p class="mt-5 text-white/75 leading-relaxed">
                Falcore is a global travel services company connecting travelers, travel businesses, airlines, technology, and industry partners through reliable travel solutions and professional expertise.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('services') }}" class="inline-flex rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-3">Explore Our Services</a>
                <a href="{{ route('partnerships') }}" class="inline-flex rounded-full border-2 border-white/70 text-white hover:bg-white hover:text-navy-950 font-semibold px-6 py-3">Partner With Falcore</a>
            </div>
        </div>

        <div id="search-flights" class="order-1 lg:order-3 relative z-10 w-full max-w-[1100px] mx-auto lg:mt-14">
            @include('partials.flight-search')
        </div>

        <div class="order-3 lg:order-2 mt-10 lg:mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['fa-solid fa-earth-americas', 'Global Travel Solutions', 'Worldwide reach. Seamless connections.'],
                ['fa-solid fa-plane', 'Aviation Expertise', 'Industry knowledge. Professional team.'],
                ['fa-regular fa-circle-check', 'Trusted Partnerships', 'Strong relationships. Global network.'],
                ['fa-regular fa-heart', 'Customer Focused', 'People at the core of everything we do.'],
            ] as $feature)
                <article class="rounded-2xl bg-white/8 border border-white/15 backdrop-blur-sm px-5 py-6 text-white">
                    <div class="h-11 w-11 rounded-full border border-gold-500 text-gold-400 inline-flex items-center justify-center">
                        <i class="{{ $feature[0] }}"></i>
                    </div>
                    <h3 class="mt-4 font-semibold">{{ $feature[1] }}</h3>
                    <p class="mt-2 text-sm text-white/65">{{ $feature[2] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="py-20">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs font-semibold tracking-[0.22em] uppercase text-gold-600">Why Falcore?</p>
            <h2 class="mt-3 font-display text-3xl sm:text-5xl font-bold">Why Travel With Falcore?</h2>
        </div>
        <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach([
                ['fa-solid fa-medal', 'Industry Expertise', 'Built around professional knowledge of air travel, ticketing, travel distribution, and customer requirements.'],
                ['fa-solid fa-globe', 'Global Perspective', 'We operate with a worldwide view of travel and connect different markets and travel ecosystems.'],
                ['fa-regular fa-handshake', 'Strong Partnerships', 'Our business is built around relationships with travel-industry partners and suppliers.'],
                ['fa-solid fa-gear', 'Operational Capability', 'We provide the behind-the-scenes expertise required to support efficient travel operations.'],
                ['fa-regular fa-user', 'Customer-Centric', 'Technology may power modern travel, but people remain at its centre.'],
                ['fa-solid fa-arrow-trend-up', 'Built for Growth', 'Our ecosystem is designed to support travellers, travel businesses, and future travel opportunities.'],
            ] as $reason)
                <article class="flex gap-4">
                    <div class="shrink-0 h-12 w-12 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center">
                        <i class="{{ $reason[0] }}"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">{{ $reason[1] }}</h3>
                        <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ $reason[2] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="iata-photo">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-20 grid lg:grid-cols-2 gap-12 items-center">
        <div class="text-white">
            <h2 class="font-display text-3xl sm:text-5xl font-bold leading-tight">Professional Travel.<br>Industry Connected.</h2>
            <p class="mt-5 text-white/80">Falcore World Travel Services is an IATA-accredited travel agency.</p>
            <p class="mt-3 text-white/70">Our industry accreditation represents our commitment to professional standards and participation in the global travel ecosystem.</p>
            <ul class="mt-6 space-y-3 text-sm">
                @foreach(['Industry Knowledge', 'Professional Standards', 'Global Connections'] as $point)
                    <li class="flex items-center gap-3">
                        <span class="h-6 w-6 rounded-full bg-gold-500 text-navy-950 inline-flex items-center justify-center text-xs"><i class="fa-solid fa-check"></i></span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-3xl border border-white/15 bg-white/5 backdrop-blur px-8 py-12 text-center text-white">
            <p class="text-gold-400 text-xs font-bold tracking-[0.22em]">IATA ACCREDITED AGENT</p>
            <p class="mt-4 font-display text-4xl font-bold">Accredited Agent</p>
            <p class="mt-3 text-white/65">Professional ticketing and distribution standards in the global travel ecosystem.</p>
        </div>
    </div>
</section>

<section>
    <div class="max-w-[1320px] mx-auto grid lg:grid-cols-12">
        <div class="lg:col-span-5 bg-navy-900 text-white px-6 sm:px-10 py-16 flex flex-col justify-center">
            <h2 class="font-display text-3xl sm:text-4xl font-bold">Why Falcore?</h2>
            <p class="mt-6 text-white/75 leading-relaxed">
                <strong class="text-gold-400">FAL</strong> — inspired by the falcon: vision, speed, freedom, and the ability to move beyond boundaries.
            </p>
            <p class="mt-4 text-white/75 leading-relaxed">
                <strong class="text-gold-400">CORE</strong> — the centre, foundation, and essential connection behind every journey.
            </p>
            <p class="mt-6 text-white/60">Together:</p>
            <p class="mt-2 text-2xl font-display font-bold">FALCORE <span class="text-gold-400 text-lg font-sans font-semibold">The Core of Every Journey</span></p>
            <p class="mt-3 text-gold-400 font-semibold">Fly Beyond Boundaries</p>
        </div>
        <div class="lg:col-span-7 min-h-[320px]">
            <img src="https://images.unsplash.com/photo-1483728642387-6c3bdd6c93e5?auto=format&fit=crop&w=1600&q=80" alt="Falcon flying above mountains" class="h-full w-full object-cover min-h-[320px]">
        </div>
    </div>
</section>

<section class="vision-photo">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-20 grid lg:grid-cols-2 gap-10">
        <div class="text-white">
            <p class="text-xs font-semibold tracking-[0.2em] uppercase text-gold-400">Our Vision</p>
            <h2 class="mt-3 font-display text-3xl sm:text-4xl font-bold">A World Where Travel Moves More Freely</h2>
            <p class="mt-5 text-white/75">We believe the future of travel belongs to companies that can connect technology with human expertise.</p>
            <p class="mt-4 text-white/80 font-medium"><i class="fa-solid fa-arrow-right-long text-gold-400 mr-2"></i> Our vision is to build a travel ecosystem that is:</p>
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                @foreach([
                    ['fa-solid fa-link', 'More Connected'],
                    ['fa-solid fa-bolt', 'More Efficient'],
                    ['fa-solid fa-network-wired', 'More Accessible'],
                    ['fa-regular fa-face-smile', 'More Human'],
                ] as $item)
                    <div class="rounded-xl border border-white/15 bg-white/5 px-4 py-4">
                        <i class="{{ $item[0] }} text-gold-400"></i>
                        <p class="mt-2 font-semibold">{{ $item[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-3xl border border-white/15 bg-white/8 px-8 py-10 text-white">
            <p class="text-xs font-semibold tracking-[0.2em] uppercase text-gold-400">Our Mission</p>
            <p class="mt-5 text-lg text-white/80 leading-relaxed">
                To create reliable, connected, and innovative travel solutions that make it easier for people and businesses to move across borders.
            </p>
            <h3 class="mt-8 font-display text-3xl font-bold">Connect. Simplify. Move.</h3>
            <p class="mt-3 text-gold-400 font-semibold">That’s Falcore.</p>
        </div>
    </div>
</section>
@endsection
