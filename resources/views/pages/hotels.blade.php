@extends('layouts.app')

@section('title', 'Hotels')

@section('content')
<section class="relative overflow-hidden">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80" alt="" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-slate-900/45"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-white">
        <p class="text-sm font-bold tracking-[0.2em]">HOTELS</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-extrabold max-w-xl">Stay close to the places you fly.</h1>
        <p class="mt-4 max-w-lg text-white/80">City hotels, beach villas, and airport lodges — pair a room with your Falcore flight.</p>
        <a href="{{ route('contact') }}" class="inline-flex mt-8 rounded-full bg-brand-500 hover:bg-brand-600 px-6 py-3 font-semibold">Talk to a planner</a>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid md:grid-cols-3 gap-6">
    @foreach([
        ['Dubai Marina', 'https://images.unsplash.com/photo-1512453979798-5ea138f9dba6?auto=format&fit=crop&w=900&q=80'],
        ['Paris Riverside', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=900&q=80'],
        ['Bali Hideaway', 'https://images.unsplash.com/photo-1537996194471-e657df975ab2?auto=format&fit=crop&w=900&q=80'],
    ] as $stay)
        <article class="rounded-2xl overflow-hidden border border-slate-100">
            <img src="{{ $stay[1] }}" alt="{{ $stay[0] }}" class="h-52 w-full object-cover">
            <div class="p-5">
                <h2 class="font-bold text-lg">{{ $stay[0] }}</h2>
                <p class="text-sm text-slate-500 mt-1">From curated partners near the airport and downtown.</p>
            </div>
        </article>
    @endforeach
</section>
@endsection
