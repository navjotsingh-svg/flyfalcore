@php
    $cards = $offers->map(fn ($offer) => \App\Support\FlightCard::fromOffer($offer, $mix, $filters ?? []))->values();
    $first = $cards->first();
    $departDate = !empty($filters['date']) ? \Carbon\Carbon::parse($filters['date'])->format('D, j M') : ($first['outbound']['depart_date'] ?? '');
    $returnDate = !empty($filters['return_date']) ? \Carbon\Carbon::parse($filters['return_date'])->format('D, j M') : ($first['inbound']['depart_date'] ?? '');
@endphp
<div id="flight-results" x-data="returnListing({{ \Illuminate\Support\Js::from($cards) }})" class="mt-4">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold tracking-[0.16em] uppercase text-gold-600">Round trip</p>
            <h2 class="mt-1 text-lg sm:text-xl font-semibold text-navy-900">
                Select depart &amp; return
                <span class="font-normal text-slate-500">· {{ $cards->count() }} combo{{ $cards->count() === 1 ? '' : 's' }}</span>
            </h2>
        </div>
        <p class="text-xs text-slate-500">Pick one flight each side. Fare updates for the pair.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-3 lg:gap-4">
        <section class="rounded-xl bg-white ring-1 ring-slate-200 overflow-hidden">
            <header class="px-4 py-3 bg-navy-950 text-white flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-gold-400">Depart</p>
                    <p class="mt-0.5 text-sm font-semibold">{{ $fromCode }} → {{ $toCode }}</p>
                </div>
                <p class="text-xs text-white/70">{{ $departDate }}</p>
            </header>
            <div class="divide-y divide-slate-100 max-h-[70vh] overflow-y-auto">
                <template x-for="row in outbounds" :key="row.key">
                    <button type="button" class="w-full text-left px-3 sm:px-4 py-3 transition"
                            :class="row.key === outboundKey ? 'bg-gold-50 ring-inset ring-2 ring-gold-400' : 'hover:bg-slate-50'"
                            @click="selectOutbound(row.key)">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="shrink-0 h-4 w-4 rounded-full border-2 inline-flex items-center justify-center"
                                  :class="row.key === outboundKey ? 'border-gold-500 bg-gold-500' : 'border-slate-300 bg-white'">
                                <span class="h-1.5 w-1.5 rounded-full bg-navy-950" x-show="row.key === outboundKey"></span>
                            </span>
                            <div class="h-9 w-9 shrink-0 rounded-lg bg-navy-950 text-gold-400 inline-flex items-center justify-center text-[11px] font-bold" x-text="row.leg.initials"></div>
                            <div class="w-24 shrink-0 min-w-0">
                                <p class="text-sm font-semibold text-navy-900 truncate" x-text="row.leg.airline"></p>
                                <p class="text-[11px] text-slate-500 truncate" x-text="row.leg.flight_number"></p>
                            </div>
                            <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-1 min-w-0">
                                <div>
                                    <p class="text-lg font-extrabold text-navy-900 leading-none tabular-nums" x-text="row.leg.depart_time"></p>
                                    <p class="mt-1 text-[11px] font-semibold text-navy-900" x-text="row.leg.origin_code"></p>
                                </div>
                                <div class="text-center px-1">
                                    <p class="text-[10px] text-slate-400" x-text="row.leg.duration"></p>
                                    <div class="my-1 w-14 mx-auto flex items-center">
                                        <span class="h-1.5 w-1.5 rounded-full bg-navy-900"></span>
                                        <span class="flex-1 border-t border-slate-300"></span>
                                        <span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span>
                                    </div>
                                    <p class="text-[10px] font-semibold" :class="row.leg.stops === 0 ? 'text-emerald-600' : 'text-amber-600'" x-text="row.leg.stop_label"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-extrabold text-navy-900 leading-none tabular-nums" x-text="row.leg.arrive_time"></p>
                                    <p class="mt-1 text-[11px] font-semibold text-navy-900" x-text="row.leg.destination_code"></p>
                                </div>
                            </div>
                            <p class="hidden sm:block shrink-0 text-sm font-bold text-navy-900" x-text="row.from_price"></p>
                        </div>
                    </button>
                </template>
            </div>
        </section>

        <section class="rounded-xl bg-white ring-1 ring-slate-200 overflow-hidden">
            <header class="px-4 py-3 bg-navy-950 text-white flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-gold-400">Return</p>
                    <p class="mt-0.5 text-sm font-semibold">{{ $toCode }} → {{ $fromCode }}</p>
                </div>
                <p class="text-xs text-white/70">{{ $returnDate }}</p>
            </header>
            <div class="divide-y divide-slate-100 max-h-[70vh] overflow-y-auto">
                <template x-for="offer in returns" :key="offer.id">
                    <button type="button" class="w-full text-left px-3 sm:px-4 py-3 transition"
                            :class="offer.id === selectedId ? 'bg-gold-50 ring-inset ring-2 ring-gold-400' : 'hover:bg-slate-50'"
                            @click="selectOffer(offer.id)">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="shrink-0 h-4 w-4 rounded-full border-2 inline-flex items-center justify-center"
                                  :class="offer.id === selectedId ? 'border-gold-500 bg-gold-500' : 'border-slate-300 bg-white'">
                                <span class="h-1.5 w-1.5 rounded-full bg-navy-950" x-show="offer.id === selectedId"></span>
                            </span>
                            <div class="h-9 w-9 shrink-0 rounded-lg bg-navy-950 text-gold-400 inline-flex items-center justify-center text-[11px] font-bold" x-text="offer.inbound.initials"></div>
                            <div class="w-24 shrink-0 min-w-0">
                                <p class="text-sm font-semibold text-navy-900 truncate" x-text="offer.inbound.airline"></p>
                                <p class="text-[11px] text-slate-500 truncate" x-text="offer.inbound.flight_number"></p>
                            </div>
                            <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-1 min-w-0">
                                <div>
                                    <p class="text-lg font-extrabold text-navy-900 leading-none tabular-nums" x-text="offer.inbound.depart_time"></p>
                                    <p class="mt-1 text-[11px] font-semibold text-navy-900" x-text="offer.inbound.origin_code"></p>
                                </div>
                                <div class="text-center px-1">
                                    <p class="text-[10px] text-slate-400" x-text="offer.inbound.duration"></p>
                                    <div class="my-1 w-14 mx-auto flex items-center">
                                        <span class="h-1.5 w-1.5 rounded-full bg-navy-900"></span>
                                        <span class="flex-1 border-t border-slate-300"></span>
                                        <span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span>
                                    </div>
                                    <p class="text-[10px] font-semibold" :class="offer.inbound.stops === 0 ? 'text-emerald-600' : 'text-amber-600'" x-text="offer.inbound.stop_label"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-extrabold text-navy-900 leading-none tabular-nums" x-text="offer.inbound.arrive_time"></p>
                                    <p class="mt-1 text-[11px] font-semibold text-navy-900" x-text="offer.inbound.destination_code"></p>
                                </div>
                            </div>
                            <p class="hidden sm:block shrink-0 text-sm font-bold text-navy-900" x-text="offer.price_label"></p>
                        </div>
                    </button>
                </template>
            </div>
        </section>
    </div>

    <div class="h-24 lg:h-20"></div>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur shadow-[0_-8px_30px_-18px_rgba(11,26,51,0.45)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0 text-sm text-navy-900" x-show="selected">
                <p class="text-[10px] font-bold tracking-[0.16em] uppercase text-gold-600">Selected pair</p>
                <p class="mt-0.5 font-semibold truncate">
                    <span x-text="selected.outbound.origin_code + ' ' + selected.outbound.depart_time"></span>
                    →
                    <span x-text="selected.outbound.destination_code + ' ' + selected.outbound.arrive_time"></span>
                    <span class="text-slate-300 px-1.5">|</span>
                    <span x-text="selected.inbound.origin_code + ' ' + selected.inbound.depart_time"></span>
                    →
                    <span x-text="selected.inbound.destination_code + ' ' + selected.inbound.arrive_time"></span>
                </p>
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-4">
                <div class="text-right">
                    <p class="text-[10px] uppercase tracking-wide text-slate-400" x-text="selected.fare_hint"></p>
                    <p class="font-display text-2xl font-bold text-navy-900 leading-none" x-text="selected.price_label"></p>
                </div>
                <a :href="selected.select_url" class="inline-flex rounded-full bg-navy-950 hover:bg-navy-900 text-white font-semibold px-6 py-2.5">Select</a>
            </div>
        </div>
    </div>
</div>
