@php
    $card = \App\Support\FlightCard::fromOffer($offer, $mix ?? \App\Support\PassengerMix::fromArray($filters ?? []), $filters ?? []);
    $roundTrip = $card['is_round_trip'];
@endphp
<article class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 hover:ring-navy-900/20 transition">
    <div class="p-4 sm:p-5 {{ $roundTrip ? 'divide-y divide-slate-100' : '' }}">
        @foreach($card['legs'] as $index => $leg)
            <div class="{{ $roundTrip && $index > 0 ? 'pt-4 mt-4' : '' }}">
                @if($roundTrip)
                    <p class="mb-2 text-[10px] font-bold tracking-[0.16em] uppercase {{ $index === 0 ? 'text-navy-900' : 'text-gold-600' }}">
                        {{ $index === 0 ? 'Depart' : 'Return' }}
                    </p>
                @endif
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-navy-950 text-gold-400 inline-flex items-center justify-center text-[11px] font-bold">{{ $leg['initials'] }}</div>
                    <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2 min-w-0">
                        <div>
                            <p class="text-xl font-extrabold text-navy-900 leading-none tabular-nums">
                                {{ $leg['depart_time'] }} <span class="text-slate-300 font-medium">–</span> {{ $leg['arrive_time'] }}
                                @if(($leg['plus_days'] ?? 0) > 0)<sup class="text-gold-600 text-[10px]">+{{ $leg['plus_days'] }}</sup>@endif
                            </p>
                            <p class="mt-1 text-sm text-slate-500 truncate">{{ $leg['airline'] }}{{ $leg['flight_number'] ? ' · '.$leg['flight_number'] : '' }}</p>
                        </div>
                        <div class="text-center px-2 hidden sm:block">
                            <p class="text-sm font-semibold text-navy-900">{{ $leg['duration'] }}</p>
                            <p class="text-xs text-slate-400">{{ $leg['origin_code'] }} – {{ $leg['destination_code'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-navy-900">{{ $leg['stop_label'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="border-t border-slate-100 px-4 sm:px-5 py-3 flex items-center justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-slate-400">{{ $card['fare_hint'] }}</p>
            <p class="font-display text-xl font-bold text-navy-900">{{ $card['price_label'] }}</p>
        </div>
        <a href="{{ $card['select_url'] }}" class="inline-flex rounded-full bg-navy-950 hover:bg-navy-900 text-white text-sm font-semibold px-5 py-2.5">
            Select
        </a>
    </div>
</article>
