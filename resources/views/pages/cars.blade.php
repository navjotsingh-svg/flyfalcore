@extends('layouts.app')

@section('title', 'Cars')

@section('content')
<section class="relative overflow-hidden">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=2000&q=80" alt="" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-slate-900/50"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-white">
        <p class="text-sm font-bold tracking-[0.2em]">CARS</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-extrabold max-w-xl">Land, pick up, keep going.</h1>
        <p class="mt-4 max-w-lg text-white/80">Airport counters and city lots in Falcore destinations. Compact, SUV, or chauffeur.</p>
        <a href="{{ route('contact') }}" class="inline-flex mt-8 rounded-full bg-brand-500 hover:bg-brand-600 px-6 py-3 font-semibold">Request a car</a>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid md:grid-cols-3 gap-6">
    @foreach(['Economy', 'SUV', 'Chauffeur'] as $type)
        <article class="rounded-2xl border border-slate-100 p-6 bg-slate-50">
            <h2 class="font-bold text-lg">{{ $type }}</h2>
            <p class="text-sm text-slate-500 mt-2">Available at major Falcore airports. Tell us your flight number and we will line it up.</p>
        </article>
    @endforeach
</section>
@endsection
