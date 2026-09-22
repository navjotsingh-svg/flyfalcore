@extends('layouts.app')

@section('title', 'Destinations')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Destinations',
    'title' => 'All destinations',
    'subtitle' => 'Choose a city and we will show upcoming Falcore flights into that airport.',
])
<section class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
            $photos = [
                'London' => 'https://images.unsplash.com/photo-1513635269971-016998ad2c9c?auto=format&fit=crop&w=900&q=80',
                'Paris' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=900&q=80',
                'Dubai' => 'https://images.unsplash.com/photo-1512453979798-5ea138f9dba6?auto=format&fit=crop&w=900&q=80',
                'New York' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?auto=format&fit=crop&w=900&q=80',
                'Singapore' => 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?auto=format&fit=crop&w=900&q=80',
                'Bangkok' => 'https://images.unsplash.com/photo-1508009603885-50cf7c556725?auto=format&fit=crop&w=900&q=80',
                'Rome' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?auto=format&fit=crop&w=900&q=80',
                'Athens' => 'https://images.unsplash.com/photo-1555993539-1732b0258235?auto=format&fit=crop&w=900&q=80',
                'Mumbai' => 'https://images.unsplash.com/photo-1529253355930-ddbe423a2ac7?auto=format&fit=crop&w=900&q=80',
                'New Delhi' => 'https://images.unsplash.com/photo-1587474260584-136574528ed5?auto=format&fit=crop&w=900&q=80',
                'Bengaluru' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=900&q=80',
            ];
        @endphp
        @foreach($airports as $airport)
            <a href="{{ route('flights.index', ['to' => $airport->id]) }}" class="group rounded-2xl overflow-hidden border border-slate-100">
                <div class="h-44 overflow-hidden bg-slate-100">
                    <img src="{{ $photos[$airport->city] ?? 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=900&q=80' }}"
                         alt="{{ $airport->city }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-500">
                </div>
                <div class="p-4">
                    <p class="font-bold">{{ $airport->city }}</p>
                    <p class="text-sm text-slate-500">{{ $airport->code }} · {{ $airport->country }}</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endsection
