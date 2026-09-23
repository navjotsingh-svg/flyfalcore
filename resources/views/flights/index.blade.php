@extends('layouts.app')

@section('title', 'Search Flights')

@section('content')
@php
    $routeReady = filled($fromLabel ?? null) && filled($toLabel ?? null);
    $mix = $mix ?? \App\Support\PassengerMix::fromArray($filters ?? []);
@endphp
<div class="bg-slate-50 min-h-[70vh]" x-data="{ modify: {{ $routeReady ? 'false' : 'true' }} }" @edit-search="modify = true">
    <section class="bg-navy-950 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-10">
            <p class="text-xs font-semibold tracking-[0.2em] uppercase text-gold-400">Live fares</p>
            <h1 class="mt-2 font-display text-2xl sm:text-4xl font-bold">
                @if($routeReady)
                    {{ $fromLabel }} <span class="text-gold-400">{{ filled($filters['return_date'] ?? null) ? '⇄' : '→' }}</span> {{ $toLabel }}
                @else
                    Search flights
                @endif
            </h1>
            <p class="mt-2 text-sm text-white/65">
                @if(!empty($filters['date']))
                    {{ \Carbon\Carbon::parse($filters['date'])->format('D, j M Y') }}
                    @if(!empty($filters['return_date']))
                        · Return {{ \Carbon\Carbon::parse($filters['return_date'])->format('D, j M') }}
                    @endif
                    ·
                @endif
                {{ $mix->summary() }}
                @if(!empty($filters['cabin']))
                    · {{ \App\Support\AirlineCopy::cabinLabel($filters['cabin']) }}
                @endif
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $routeReady ? 'pt-4 sm:pt-0 sm:-mt-5' : '-mt-5' }} pb-14">
        @if($searchMessage)
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">{{ $searchMessage }}</div>
        @endif

        @if($routeReady)
            <button type="button"
                    class="lg:hidden mb-4 w-full rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 px-4 py-3 flex items-center justify-between gap-3 text-left"
                    @click="modify = !modify">
                <span>
                    <span class="block text-sm font-semibold text-navy-900">{{ $fromCode }} {{ filled($filters['return_date'] ?? null) ? '⇄' : '→' }} {{ $toCode }}</span>
                    <span class="block mt-0.5 text-xs text-slate-500">
                        {{ !empty($filters['date']) ? \Carbon\Carbon::parse($filters['date'])->format('D, j M') : '' }}
                        · {{ $mix->summary() }}
                    </span>
                </span>
                <span class="shrink-0 text-xs font-semibold text-gold-600" x-text="modify ? 'Hide' : 'Modify search'"></span>
            </button>
        @endif

        <div class="{{ $routeReady ? 'hidden lg:block' : 'block' }}" :class="{ '!block': modify }">
            <form action="{{ route('flights.index') }}" method="GET"
                  class="rounded-[24px] border border-white bg-white p-4 sm:p-5 shadow-search"
                  x-data="airportPair({
                      fromCode: @js($fromCode ?? ''),
                      fromQuery: @js($fromLabel ?? ''),
                      toCode: @js($toCode ?? ''),
                      toQuery: @js($toLabel ?? ''),
                      url: @js(route('airports.suggest')),
                      trip: @js($trip ?? 'oneway'),
                      depart: @js($filters['date'] ?? now()->addDay()->toDateString()),
                      cabin: @js($filters['cabin'] ?? ''),
                  })"
                  @submit="if (!validate()) $event.preventDefault()">
            @include('partials.trip-type', ['wrapClass' => 'mb-4'])
            @include('partials.cabin-class-picker', ['includeAny' => true, 'wrapClass' => 'mb-4'])
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                @include('partials.airport-suggest', [
                    'showSwap' => false,
                    'inputClass' => 'w-full rounded-2xl border border-slate-200 px-3 py-2.5 bg-slate-50 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500',
                    'labelClass' => 'block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1',
                ])
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Date</label>
                    <input type="date" name="date" x-model="depart" min="{{ now()->toDateString() }}" required
                           class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                </div>
                <div x-show="trip === 'return'" x-cloak>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Return</label>
                    <input type="date" name="return_date" value="{{ $filters['return_date'] ?? '' }}"
                           :min="depart || '{{ now()->toDateString() }}'"
                           :required="trip === 'return'"
                           :disabled="trip !== 'return'"
                           class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                </div>
                @include('partials.traveller-mix', [
                    'wrapClass' => '',
                    'labelClass' => 'block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1',
                    'buttonClass' => 'w-full rounded-2xl border border-slate-200 px-3 py-2.5 bg-slate-50 text-sm font-medium text-left focus:outline-none focus:ring-2 focus:ring-gold-500',
                    'mix' => $mix,
                ])
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Sort</label>
                    <select name="sort" class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                        <option value="price" @selected(($filters['sort'] ?? 'price') === 'price')>Lowest price</option>
                        <option value="duration" @selected(($filters['sort'] ?? '') === 'duration')>Shortest</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-6 py-2.5 transition">
                    Search flights
                </button>
            </div>
        </form>
        </div>

        @php
            $isReturnListing = filled($filters['return_date'] ?? null)
                && $offers->contains(fn ($offer) => ! empty($offer['is_round_trip']));
        @endphp

        @if($isReturnListing)
            @include('partials.return-listing')
            <noscript>
                <div class="mt-5 space-y-3">
                    @foreach($offers as $offer)
                        @include('partials.flight-offer-card', ['offer' => $offer, 'mix' => $mix, 'filters' => $filters ?? []])
                    @endforeach
                </div>
            </noscript>
        @elseif($offers->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center">
                <div class="mx-auto h-14 w-14 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center">
                    <i class="fa-solid fa-plane-circle-exclamation"></i>
                </div>
                <p class="mt-4 font-semibold text-navy-900">No flights match this search</p>
                <p class="mt-2 text-sm text-slate-500">Try another date, cabin, or nearby airport.</p>
            </div>
        @else
            @include('partials.flight-listing')
            <noscript>
                <div class="mt-5 space-y-3">
                    @foreach($offers as $offer)
                        @include('partials.flight-offer-card', ['offer' => $offer, 'mix' => $mix, 'filters' => $filters ?? []])
                    @endforeach
                </div>
            </noscript>
        @endif
    </section>
</div>
@endsection
