@php
    $ancillaries = $ancillaries ?? ['bags' => [], 'seats' => [], 'letters' => ['A','B','C','D','E','F'], 'aisle_after' => 'C', 'cabin_label' => 'Economy', 'currency' => $currency ?? 'USD'];
    $letters = $ancillaries['letters'] ?? ['A', 'B', 'C', 'D', 'E', 'F'];
    $aisleAfter = $ancillaries['aisle_after'] ?? 'C';
    $rows = collect($ancillaries['seats'] ?? [])->groupBy('row')->sortKeys();
    $seatedIndexes = [];
    foreach ($passengerSlots as $index => $slot) {
        if (($slot['type'] ?? 'adult') !== 'infant_without_seat') {
            $seatedIndexes[] = $index;
        }
    }
    $colCount = count($letters) + 1;
    $hasBags = collect($ancillaries['bags'] ?? [])->filter()->isNotEmpty();
@endphp
@if($seatedIndexes !== [])
<style>
    .cabin-shell {
        background:
            radial-gradient(120% 80% at 50% -10%, rgba(212,176,106,0.16), transparent 46%),
            linear-gradient(180deg, #12233d 0%, #0b1a33 42%, #081424 100%);
    }
    .cabin-seat {
        height: 2.15rem;
        border-radius: 8px 8px 11px 11px;
        box-shadow: inset 0 -4px 0 rgba(11, 26, 51, 0.12);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
</style>

<div class="space-y-3">
    <h2 class="font-semibold text-lg text-navy-900">Add extras</h2>

    @if($hasBags)
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 overflow-hidden">
            <button type="button" class="w-full px-5 py-4 flex items-center justify-between gap-3 text-left" @click="extrasOpen = extrasOpen === 'bags' ? '' : 'bags'">
                <span class="flex items-start gap-3">
                    <span class="mt-0.5 h-9 w-9 rounded-xl bg-slate-100 text-navy-900 inline-flex items-center justify-center"><i class="fa-solid fa-suitcase"></i></span>
                    <span>
                        <span class="block font-semibold text-navy-900">Extra baggage</span>
                        <span class="block text-sm text-slate-500">Add any extra bags you need for your trip</span>
                    </span>
                </span>
                <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition" :class="extrasOpen === 'bags' ? 'rotate-180' : ''"></i>
            </button>
            <div class="px-5 pb-5 space-y-3" x-show="extrasOpen === 'bags'" x-cloak>
                @foreach($passengerSlots as $i => $slot)
                    @continue(($slot['type'] ?? 'adult') === 'infant_without_seat')
                    @php $bag = $ancillaries['bags'][$i] ?? null; @endphp
                    @if($bag)
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="font-semibold text-sm">Passenger {{ $i + 1 }} · {{ $slot['label'] }}</p>
                            <label class="mt-3 block text-sm">
                                <span class="font-medium">Extra checked bag</span>
                                <span class="text-slate-500"> · {{ $symbol }}{{ number_format($bag['amount'], 2) }} each</span>
                                <select name="extras[bags][{{ $i }}]" x-model.number="bags[{{ $i }}]"
                                        class="mt-1 w-full sm:w-56 rounded-xl border border-slate-200 px-3 py-2.5 bg-white text-sm">
                                    <option value="0">No extra bag</option>
                                    @for($qty = 1; $qty <= (int) $bag['max']; $qty++)
                                        <option value="{{ $qty }}">{{ $qty }} × {{ $bag['label'] }}</option>
                                    @endfor
                                </select>
                            </label>
                            @error("extras.bags.$i") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl bg-white ring-1 ring-slate-200 overflow-hidden">
        <button type="button" class="w-full px-5 py-4 flex items-center justify-between gap-3 text-left" @click="seatModal = true">
            <span class="flex items-start gap-3">
                <span class="mt-0.5 h-9 w-9 rounded-xl bg-slate-100 text-navy-900 inline-flex items-center justify-center"><i class="fa-solid fa-chair"></i></span>
                <span>
                    <span class="block font-semibold text-navy-900">Choose a seat</span>
                    <span class="block text-sm text-slate-500">Specify where on the plane you’d like to sit</span>
                    <span class="block mt-1 text-sm font-semibold text-navy-900" x-show="seatedIndexes().some((index) => seats[index])" x-text="seatedIndexes().filter((index) => seats[index]).map((index) => 'P' + (index + 1) + ' ' + seats[index]).join(' · ')"></span>
                </span>
            </span>
            <i class="fa-solid fa-plus text-slate-400 text-xs"></i>
        </button>
    </div>

    @foreach($passengerSlots as $i => $slot)
        @continue(($slot['type'] ?? 'adult') === 'infant_without_seat')
        <input type="hidden" name="extras[seats][{{ $i }}]" x-model="seats[{{ $i }}]">
        @error("extras.seats.$i") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    @endforeach
</div>

<div class="fixed inset-0 z-50" x-show="seatModal" x-cloak>
    <div class="absolute inset-0 bg-navy-950/50" @click="seatModal = false"></div>
    <div class="relative mx-auto mt-6 mb-6 w-[min(52rem,calc(100%-1.5rem))] max-h-[calc(100vh-3rem)] overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur px-5 sm:px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-navy-900">{{ $offer['origin_code'] ?? '' }} → {{ $offer['destination_code'] ?? '' }}</p>
                <p class="text-sm text-slate-500">Choose a seat · <span x-text="passengerLabel(selecting)"></span></p>
            </div>
            <button type="button" class="h-9 w-9 rounded-full hover:bg-slate-100" @click="seatModal = false">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="px-5 sm:px-6 py-4 flex flex-wrap gap-x-4 gap-y-2 text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-[#f4f1ea] ring-1 ring-slate-200"></span> Additional cost</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-white ring-1 ring-slate-300"></span> Included</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-navy-950"></span> Selected</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-slate-200 text-center leading-3">×</span> Unavailable</span>
        </div>

        <div class="px-5 sm:px-6 pb-3">
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($seatedIndexes as $index)
                    <button type="button"
                            class="rounded-2xl border px-3 py-3 text-left transition"
                            :class="selecting === {{ $index }} ? 'border-navy-950 bg-slate-50' : 'border-slate-200 bg-white hover:border-gold-300'"
                            @click="selecting = {{ $index }}">
                        <div class="flex items-center gap-3">
                            <span class="h-10 w-10 rounded-xl bg-navy-950 text-gold-400 inline-flex items-center justify-center text-sm font-bold"
                                  x-text="passengerInitial({{ $index }})">P{{ $index + 1 }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-navy-900 truncate" x-text="passengerLabel({{ $index }})">Passenger {{ $index + 1 }}</span>
                                <span class="block text-xs text-slate-500 mt-0.5" x-text="seats[{{ $index }}] ? ('Seat ' + seats[{{ $index }}] + seatPriceLabel(seats[{{ $index }}])) : 'Tap a seat on the map'"></span>
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="cabin-shell mx-5 sm:mx-6 mb-4 overflow-hidden rounded-[28px]">
            <div class="px-4 pt-5 text-center">
                <p class="text-[10px] font-bold tracking-[0.24em] uppercase text-gold-400">Front of cabin</p>
                <div class="mx-auto mt-3 h-8 w-40 rounded-t-full border-x border-t border-white/15 bg-white/5"></div>
            </div>
            <div class="overflow-x-auto px-3 sm:px-6 pb-6">
                <div class="min-w-[22rem] max-w-lg mx-auto">
                    <div class="grid items-center gap-1.5 text-[10px] text-gold-400/80 font-semibold px-8 mb-2" style="grid-template-columns: 1.6rem repeat({{ $colCount }}, minmax(1.85rem, 1fr));">
                        <span></span>
                        @foreach($letters as $letter)
                            <span class="text-center">{{ $letter }}</span>
                            @if($letter === $aisleAfter)
                                <span class="text-center text-white/25 text-[9px] tracking-widest">AISLE</span>
                            @endif
                        @endforeach
                    </div>
                    @foreach($rows as $rowNumber => $rowSeats)
                        @php
                            $byLetter = $rowSeats->keyBy('letter');
                            $isExit = collect($rowSeats)->contains(fn ($seat) => ! empty($seat['extra']));
                        @endphp
                        @if($isExit)
                            <div class="my-2 flex items-center gap-2 text-[9px] font-bold tracking-[0.2em] uppercase text-emerald-300/80">
                                <span class="flex-1 border-t border-dashed border-emerald-300/30"></span>
                                Exit row
                                <span class="flex-1 border-t border-dashed border-emerald-300/30"></span>
                            </div>
                        @endif
                        <div class="grid items-center gap-1.5" style="grid-template-columns: 1.6rem repeat({{ $colCount }}, minmax(1.85rem, 1fr));">
                            <span class="text-[11px] text-white/45 text-right pr-1 tabular-nums">{{ $rowNumber }}</span>
                            @foreach($letters as $letter)
                                @php $seat = $byLetter->get($letter); @endphp
                                @if($seat)
                                    <button type="button"
                                            class="cabin-seat"
                                            :disabled="{{ $seat['available'] ? 'false' : 'true' }}"
                                            @click="pickSeat('{{ $seat['designator'] }}')"
                                            @mouseenter="previewSeat = seatMeta('{{ $seat['designator'] }}')"
                                            @mouseleave="previewSeat = null"
                                            :class="seatClass('{{ $seat['designator'] }}', {{ $seat['available'] ? 'true' : 'false' }}, '{{ $seat['kind'] ?? 'middle' }}', {{ !empty($seat['extra']) ? 'true' : 'false' }})"
                                            title="{{ $seat['designator'] }}{{ $seat['available'] && $seat['amount'] > 0 ? ' · extra '.$symbol.number_format($seat['amount'], 0) : '' }}">
                                        <span x-text="seatMark('{{ $seat['designator'] }}', {{ $seat['available'] ? 'true' : 'false' }}, '{{ $letter }}')">{{ $seat['available'] ? $letter : '×' }}</span>
                                    </button>
                                @else
                                    <span class="h-9"></span>
                                @endif
                                @if($letter === $aisleAfter)
                                    <span class="h-9 flex items-center justify-center"><span class="h-7 w-px bg-white/10"></span></span>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                    <div class="mt-4 text-center">
                        <div class="mx-auto h-6 w-40 rounded-b-full border-x border-b border-white/15 bg-white/5"></div>
                        <p class="mt-2 text-[10px] font-bold tracking-[0.24em] uppercase text-white/35">Rear of cabin</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-white border-t border-slate-100 px-5 sm:px-6 py-4">
            <div class="flex items-center justify-between gap-3 text-sm">
                <p class="font-semibold text-navy-900" x-text="seats[selecting] ? (seats[selecting] + ' seat') : 'No seat selected'"></p>
                <p class="font-semibold text-navy-900" x-text="seats[selecting] ? (seatPriceLabel(seats[selecting]).replace(' · ', '') || 'Included') : ''"></p>
            </div>
            <div class="mt-2 flex items-center justify-between text-sm text-slate-500">
                <span>Price for selected seats</span>
                <span class="font-semibold text-navy-900" x-text="'+' + formatMoney(seatTotal())"></span>
            </div>
            <button type="button" class="mt-4 w-full rounded-full bg-navy-950 hover:bg-navy-900 text-white font-semibold py-3" @click="seatModal = false">
                Confirm
            </button>
        </div>
    </div>
</div>
@endif
