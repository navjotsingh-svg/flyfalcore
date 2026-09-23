@php
    $cards = $offers->map(fn ($offer) => \App\Support\FlightCard::fromOffer($offer, $mix, $filters ?? []))->values();
    $airlines = $cards->map(fn ($card) => [
        'code' => $card['airline_code'],
        'name' => $card['airline'],
    ])->unique('code')->sortBy('name')->values();
@endphp
<div id="flight-results" class="mt-4 lg:mt-8" x-data="flightListing({{ \Illuminate\Support\Js::from($cards) }})">
    <div class="grid lg:grid-cols-[16.5rem_minmax(0,1fr)] gap-6 items-start">
        <aside class="space-y-4">
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
                <p class="text-[11px] font-bold tracking-[0.16em] uppercase text-gold-600">Your search</p>
                <h2 class="mt-1 font-semibold text-navy-900">
                    {{ $fromCode ?: 'From' }} {{ filled($filters['return_date'] ?? null) ? '⇄' : '→' }} {{ $toCode ?: 'To' }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ !empty($filters['date']) ? \Carbon\Carbon::parse($filters['date'])->format('j M Y') : '' }}
                    · {{ $mix->summary() }}
                    @if(!empty($filters['cabin']))
                        · {{ \App\Support\AirlineCopy::cabinLabel($filters['cabin']) }}
                    @endif
                </p>
                <button type="button" class="mt-3 w-full rounded-full border border-slate-200 px-3 py-2 text-sm font-semibold text-navy-900 hover:border-gold-400" @click="$dispatch('edit-search')">
                    Edit search
                </button>
            </div>

            <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
                <p class="text-sm font-semibold text-navy-900">Sort by</p>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <label class="flex items-center gap-2"><input type="radio" name="listing-sort" value="price" x-model="sort" class="text-navy-950 focus:ring-gold-500"> Least expensive</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-sort" value="price-desc" x-model="sort" class="text-navy-950 focus:ring-gold-500"> Most expensive</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-sort" value="duration" x-model="sort" class="text-navy-950 focus:ring-gold-500"> Shortest duration</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-sort" value="duration-desc" x-model="sort" class="text-navy-950 focus:ring-gold-500"> Longest duration</label>
                </div>
            </div>

            <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
                <p class="text-sm font-semibold text-navy-900">Stops</p>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <label class="flex items-center gap-2"><input type="radio" name="listing-stops" value="0" x-model="stops" class="text-navy-950 focus:ring-gold-500"> Direct only</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-stops" value="1" x-model="stops" class="text-navy-950 focus:ring-gold-500"> 1 stop at most</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-stops" value="2" x-model="stops" class="text-navy-950 focus:ring-gold-500"> 2 stops at most</label>
                    <label class="flex items-center gap-2"><input type="radio" name="listing-stops" value="any" x-model="stops" class="text-navy-950 focus:ring-gold-500"> Any number of stops</label>
                </div>
            </div>

            <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-4">
                <p class="text-sm font-semibold text-navy-900 mb-2">Airlines</p>
                <select x-model="airline" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50 text-sm">
                    <option value="">All airlines</option>
                    @foreach($airlines as $airline)
                        <option value="{{ $airline['code'] }}">{{ $airline['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </aside>

        <div>
            <div class="flex items-end justify-between gap-4 mb-4">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] uppercase text-gold-600">
                        <span x-text="visible.length"></span> {{ $cards->count() === 1 ? 'option' : 'options' }}
                    </p>
                    <h2 class="mt-1 text-xl font-semibold text-navy-900">
                        Select flights
                        @if($routeReady)
                            <span class="font-normal text-slate-500">· {{ $fromCode }} → {{ $toCode }}</span>
                        @endif
                    </h2>
                </div>
            </div>

            <div class="space-y-3">
                <template x-for="offer in visible" :key="offer.id">
                    <article class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 hover:ring-navy-900/20 transition">
                        <div class="p-4 sm:p-5">
                            <template x-for="(leg, index) in offer.legs" :key="leg.key">
                                <div class="flex items-center gap-3 min-w-0" :class="index > 0 ? 'mt-4 pt-4 border-t border-slate-100' : ''">
                                    <div class="h-9 w-9 shrink-0 rounded-lg bg-navy-950 text-gold-400 inline-flex items-center justify-center text-[11px] font-bold" x-text="leg.initials"></div>
                                    <div class="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-2 min-w-0">
                                        <div>
                                            <p class="text-xl font-extrabold text-navy-900 leading-none tabular-nums">
                                                <span x-text="leg.depart_time"></span>
                                                <span class="text-slate-300 font-medium"> – </span>
                                                <span x-text="leg.arrive_time"></span>
                                                <sup class="text-gold-600 text-[10px]" x-show="leg.plus_days > 0" x-text="'+' + leg.plus_days"></sup>
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500 truncate">
                                                <span x-text="leg.airline"></span>
                                                <span x-text="leg.flight_number ? ' · ' + leg.flight_number : ''"></span>
                                            </p>
                                        </div>
                                        <div class="text-center px-2 hidden sm:block">
                                            <p class="text-sm font-semibold text-navy-900" x-text="leg.duration"></p>
                                            <p class="text-xs text-slate-400" x-text="leg.origin_code + ' – ' + leg.destination_code"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-navy-900" x-text="leg.stop_label"></p>
                                            <p class="text-xs text-slate-400 sm:hidden" x-text="leg.duration"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="border-t border-slate-100 px-4 sm:px-5 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-slate-400" x-text="offer.fare_hint"></p>
                                <p class="font-display text-xl font-bold text-navy-900" x-text="offer.price_label"></p>
                            </div>
                            <a :href="offer.select_url" class="inline-flex rounded-full bg-navy-950 hover:bg-navy-900 text-white text-sm font-semibold px-5 py-2.5">
                                Select
                            </a>
                        </div>
                    </article>
                </template>

                <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center" x-show="visible.length === 0">
                    <p class="font-semibold text-navy-900">No flights match these filters</p>
                    <p class="mt-2 text-sm text-slate-500">Try another stop filter or airline.</p>
                </div>
            </div>
        </div>
    </div>
</div>
