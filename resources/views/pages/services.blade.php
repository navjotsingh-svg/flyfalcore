@extends('layouts.app')

@section('title', 'Services')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Services',
    'title' => 'One Travel Company.<br>Multiple Possibilities.',
    'subtitle' => 'Falcore offers a wide range of travel solutions for individuals, businesses, and travel industry partners.',
])

<section class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach([
            ['fa-solid fa-plane', 'Air Travel Services', 'Domestic & international flight booking with best options and competitive fares.', 'flights.index'],
            ['fa-solid fa-earth-americas', 'Global Travel Solutions', 'Connecting you to destinations across countries with worldwide reliable solutions.', 'destinations'],
            ['fa-solid fa-briefcase', 'Corporate Travel', 'Tailored travel solutions for businesses and professionals.', 'corporate'],
            ['fa-regular fa-sun', 'Leisure Travel', 'Personalized travel for vacations, family trips, honeymoons, and getaways.', 'contact'],
            ['fa-solid fa-users', 'Family & Group Travel', 'Specially designed plans for families and groups of any size.', 'contact'],
            ['fa-solid fa-handshake', 'Travel Industry Services', 'Operational & booking support for travel agencies and industry professionals.', 'partnerships'],
        ] as $service)
            <a href="{{ route($service[3]) }}" class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm hover:shadow-card hover:-translate-y-0.5 transition">
                <div class="h-12 w-12 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center">
                    <i class="{{ $service[0] }}"></i>
                </div>
                <h2 class="mt-5 text-xl font-semibold">{{ $service[1] }}</h2>
                <p class="mt-3 text-sm text-slate-500 leading-relaxed">{{ $service[2] }}</p>
            </a>
        @endforeach
    </div>
    <p class="mt-12 text-center text-navy-900 font-semibold">Backed by expertise. Driven by technology. Connected by trust.</p>
</section>
@endsection
