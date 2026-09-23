@extends('layouts.app')

@section('title', 'Fare options')

@section('content')
@php
    $card = \App\Support\FlightCard::fromOffer($offer, $mix);
    $selectedId = (string) $offer['id'];
    $rows = collect($cards)->map(function (array $fare) use ($mix) {
        $terms = $fare['terms'] ?? \App\Support\FareFamily::termsForLocal($fare['cabin_class'] ?? 'economy', $fare['currency'] ?? 'USD');
        $checkout = ($fare['source'] ?? '') === 'duffel'
            ? route('offers.book', $fare['id'])
            : route('bookings.create', array_filter(array_merge(
                ['flight' => $fare['id']],
                $mix->query(),
                filled($fare['return_flight_id'] ?? null) ? ['return_flight' => $fare['return_flight_id']] : [],
            )));

        return [
            'id' => (string) $fare['id'],
            'cabin' => $fare['cabin_label'] ?? \App\Support\AirlineCopy::cabinLabel($fare['cabin_class'] ?? 'economy'),
            'brand' => $terms['fare_brand'] ?? 'Standard',
            'price' => (float) $fare['price'],
            'price_label' => \App\Support\FareFamily::money($fare['price'], $fare['currency'] ?? 'USD'),
            'airline' => $fare['airline'] ?? '',
            'checkout' => $checkout,
            'change' => $terms['change'] ?? ['allowed' => false, 'label' => 'Change policy from the airline'],
            'refund' => $terms['refund'] ?? ['allowed' => false, 'label' => 'Refund policy from the airline'],
            'hold' => $terms['hold'] ?? ['allowed' => true, 'label' => 'Hold price & space'],
            'bags' => $terms['bags'] ?? [],
            'emissions' => $terms['emissions'] ?? null,
        ];
    })->values();
@endphp
<div class="bg-slate-50 min-h-[70vh]" x-data="fareOptions({{ \Illuminate\Support\Js::from($rows) }}, {{ \Illuminate\Support\Js::from($selectedId) }})">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <nav class="text-xs text-slate-400 flex flex-wrap items-center gap-2">
            <a href="{{ route('flights.index') }}" class="hover:text-navy-900">Search</a>
            <span>·</span>
            <span>{{ $card['outbound']['origin_code'] }} to {{ $card['outbound']['destination_code'] }}</span>
            <span>·</span>
            <span class="text-navy-900 font-semibold">Fare options</span>
        </nav>

        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold tracking-[0.18em] uppercase text-gold-600">
                    {{ $offer['is_round_trip'] ?? false ? 'Round trip' : 'Flight' }}
                    · {{ $card['outbound']['depart_date'] }}
                </p>
                <h1 class="mt-1 font-display text-2xl sm:text-3xl font-bold text-navy-900">
                    {{ $card['outbound']['origin_code'] }}
                    <span class="text-gold-500">{{ ($offer['is_round_trip'] ?? false) ? '⇄' : '→' }}</span>
                    {{ $card['outbound']['destination_code'] }}
                </h1>
            </div>
        </div>

        <div class="mt-6 rounded-3xl bg-white ring-1 ring-slate-200 px-4 sm:px-6 py-5">
            @include('partials.flight-leg-row', ['leg' => $card['outbound']])
            @if(!empty($card['inbound']))
                <div class="mt-4 pt-4 border-t border-slate-100">
                    @include('partials.flight-leg-row', ['leg' => $card['inbound']])
                </div>
            @endif
        </div>

        <div class="mt-8 grid lg:grid-cols-[minmax(0,1fr)_19rem] gap-6 items-start">
            <div class="grid sm:grid-cols-2 {{ count($rows) >= 3 ? 'xl:grid-cols-3' : '' }} {{ count($rows) >= 4 ? 'xl:grid-cols-4' : '' }} gap-3">
                <template x-for="fare in fares" :key="fare.id">
                    <button type="button"
                            class="text-left rounded-2xl bg-white px-4 py-5 ring-1 transition h-full"
                            :class="fare.id === selectedId ? 'ring-2 ring-navy-950 shadow-sm' : 'ring-slate-200 hover:ring-gold-400'"
                            @click="selectedId = fare.id">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold tracking-[0.16em] uppercase text-slate-400" x-text="fare.cabin"></p>
                                <p class="mt-1 text-lg font-semibold text-navy-900" x-text="fare.brand"></p>
                            </div>
                            <span class="h-6 w-6 rounded-full border inline-flex items-center justify-center"
                                  :class="fare.id === selectedId ? 'border-navy-950 bg-navy-950 text-white' : 'border-slate-300 text-transparent'">
                                <i class="fa-solid fa-check text-[10px]"></i>
                            </span>
                        </div>

                        <ul class="mt-5 space-y-2.5 text-sm text-slate-600">
                            <li class="flex items-start gap-2">
                                <i class="fa-solid mt-0.5 w-4 text-center" :class="fare.change.allowed ? 'fa-rotate text-navy-900' : 'fa-xmark text-slate-400'"></i>
                                <span x-text="fare.change.label"></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid mt-0.5 w-4 text-center" :class="fare.refund.allowed ? 'fa-rotate-left text-navy-900' : 'fa-xmark text-slate-400'"></i>
                                <span x-text="fare.refund.label"></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fa-solid fa-clock mt-0.5 w-4 text-center text-navy-900"></i>
                                <span x-text="fare.hold.label"></span>
                            </li>
                            <template x-for="bag in fare.bags" :key="bag.type">
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-suitcase mt-0.5 w-4 text-center" :class="bag.included ? 'text-navy-900' : 'text-slate-400'"></i>
                                    <span x-text="bag.label"></span>
                                </li>
                            </template>
                        </ul>

                        <p class="mt-6 text-xs text-slate-400">Total amount from</p>
                        <p class="mt-1 font-display text-xl font-bold text-navy-900" x-text="fare.price_label"></p>
                    </button>
                </template>
            </div>

            <aside class="rounded-3xl bg-white ring-1 ring-slate-200 p-5 lg:sticky lg:top-24">
                <h2 class="text-lg font-semibold text-navy-900">Summary</h2>
                <p class="mt-1 text-sm text-slate-500">Sold by <span class="font-semibold text-navy-900" x-text="selected.airline"></span></p>

                <ul class="mt-5 space-y-2.5 text-sm text-slate-600">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid mt-0.5 w-4 text-center" :class="selected.change.allowed ? 'fa-rotate text-navy-900' : 'fa-xmark text-slate-400'"></i>
                        <span x-text="selected.change.label"></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid mt-0.5 w-4 text-center" :class="selected.refund.allowed ? 'fa-rotate-left text-navy-900' : 'fa-xmark text-slate-400'"></i>
                        <span x-text="selected.refund.label"></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-clock mt-0.5 w-4 text-center text-navy-900"></i>
                        <span x-text="selected.hold.label"></span>
                    </li>
                    <template x-for="bag in selected.bags" :key="'sum-'+bag.type">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-suitcase mt-0.5 w-4 text-center" :class="bag.included ? 'text-navy-900' : 'text-slate-400'"></i>
                            <span x-text="bag.label"></span>
                        </li>
                    </template>
                    <li class="flex items-start gap-2" x-show="selected.emissions">
                        <i class="fa-solid fa-leaf mt-0.5 w-4 text-center text-navy-900"></i>
                        <span x-text="selected.emissions"></span>
                    </li>
                </ul>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="text-xs text-slate-400">Total amount</p>
                    <p class="mt-1 font-display text-2xl font-bold text-navy-900" x-text="selected.price_label"></p>
                </div>

                <a :href="selected.checkout"
                   class="mt-5 w-full inline-flex items-center justify-center gap-2 rounded-full bg-navy-950 hover:bg-navy-900 text-white font-semibold px-5 py-3 transition">
                    Go to checkout
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </aside>
        </div>
    </section>
</div>
@endsection
