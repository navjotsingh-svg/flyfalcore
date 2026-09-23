@php
    $itineraryCard = \App\Support\FlightCard::fromOffer(
        $offer,
        $mix ?? \App\Support\PassengerMix::fromArray([]),
        []
    );
    $cabinLabel = $cabinLabel ?? ($offer['cabin_label'] ?? 'Economy');
@endphp
<div class="rounded-3xl bg-white ring-1 ring-slate-200 p-5 sm:p-6">
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">{{ ($offer['is_round_trip'] ?? false) ? 'Return' : 'One way' }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">{{ $itineraryCard['outbound']['depart_date'] }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">{{ $mix?->summary() ?? ($passengers.' passenger'.($passengers > 1 ? 's' : '')) }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-600">{{ $cabinLabel }}</span>
    </div>

    <div class="mt-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-bold text-navy-900">
                {{ $itineraryCard['outbound']['origin_code'] }}
                <span class="text-gold-500">{{ ($offer['is_round_trip'] ?? false) ? '⇄' : '→' }}</span>
                {{ $itineraryCard['outbound']['destination_code'] }}
            </h2>
            @if(!empty($offer['expires_at']))
                <p class="mt-1 text-sm text-slate-500">This offer expires on {{ $offer['expires_at']->format('d/m/Y, H:i') }}</p>
            @endif
        </div>
        <p class="text-sm text-slate-500">{{ $itineraryCard['airline'] }} · {{ $cabinLabel }}</p>
    </div>

    <div class="mt-6 space-y-5">
        @foreach($itineraryCard['legs'] as $index => $leg)
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-navy-900">
                        {{ $leg['depart_date'] }}
                        <span class="text-slate-400 font-normal">{{ $leg['depart_time'] }} – {{ $leg['arrive_time'] }}</span>
                    </p>
                    <p class="text-sm text-slate-500">{{ $leg['duration'] }} · {{ $leg['stop_label'] }}</p>
                </div>
                <ol class="mt-3 space-y-3">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full ring-4 ring-slate-100 bg-navy-950"></span>
                        <div>
                            <p class="text-sm font-semibold text-navy-900">{{ $leg['depart_time'] }} · {{ $leg['origin_code'] }}</p>
                            <p class="text-sm text-slate-500">Departure from {{ $offer['legs'][$index]['origin_name'] ?? $leg['origin_city'] }}</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full ring-4 ring-slate-100 bg-gold-500"></span>
                        <div>
                            <p class="text-sm font-semibold text-navy-900">{{ $leg['arrive_time'] }} · {{ $leg['destination_code'] }}</p>
                            <p class="text-sm text-slate-500">Arrival at {{ $offer['legs'][$index]['destination_name'] ?? $leg['destination_city'] }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        @endforeach
    </div>

    @if(!empty($offer['terms']['change']['label']))
        <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <p class="font-semibold text-navy-900">Flight change policy</p>
            <p class="mt-1">{{ $offer['terms']['change']['label'] }}{{ $offer['terms']['change']['detail'] ? ' · '.$offer['terms']['change']['detail'] : '' }}</p>
        </div>
    @endif
</div>
