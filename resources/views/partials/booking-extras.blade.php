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
@endphp
@if($seatedIndexes !== [])
<div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold tracking-[0.16em] uppercase text-gold-600">Add-ons</p>
            <h2 class="mt-1 font-semibold text-lg">Cabin, seats and extra bags</h2>
            <p class="mt-1 text-sm text-slate-500">Your fare is <span class="capitalize font-semibold text-navy-900">{{ $ancillaries['cabin_label'] ?? 'Economy' }}</span>. Optional seats and bags are added to the total before payment.</p>
        </div>
        <span class="rounded-full bg-navy-950 text-gold-400 text-xs font-semibold px-3 py-1.5 capitalize">{{ $ancillaries['cabin_label'] ?? 'Economy' }}</span>
    </div>

    <div class="space-y-4">
        @foreach($passengerSlots as $i => $slot)
            @continue(($slot['type'] ?? 'adult') === 'infant_without_seat')
            @php $bag = $ancillaries['bags'][$i] ?? null; @endphp
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-semibold text-sm">Passenger {{ $i + 1 }} · {{ $slot['label'] }}</p>
                    <span class="text-xs text-slate-500" x-text="seats[{{ $i }}] ? ('Seat ' + seats[{{ $i }}]) : 'No seat selected'"></span>
                </div>
                @if($bag)
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
                @endif
                <input type="hidden" name="extras[seats][{{ $i }}]" x-model="seats[{{ $i }}]">
                @error("extras.seats.$i") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        @endforeach
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h3 class="font-semibold">Choose a seat</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($seatedIndexes as $index)
                    <button type="button"
                            class="rounded-full px-3 py-1.5 text-xs font-semibold border"
                            :class="selecting === {{ $index }} ? 'bg-gold-500 text-navy-950 border-gold-500' : 'border-slate-200 text-slate-600'"
                            @click="selecting = {{ $index }}">
                        Passenger {{ $index + 1 }}
                    </button>
                @endforeach
            </div>
        </div>
        <p class="text-xs text-slate-500 mb-3">Tap a seat for the selected traveller. Window seats are A and F.</p>
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-navy-950 p-4">
            <div class="min-w-[22rem] space-y-1">
                <div class="grid items-center gap-1 text-[10px] text-gold-400/80 font-semibold px-10 mb-2" style="grid-template-columns: 2rem repeat({{ count($letters) + 1 }}, minmax(1.8rem, 1fr));">
                    <span></span>
                    @foreach($letters as $letter)
                        <span class="text-center">{{ $letter }}</span>
                        @if($letter === $aisleAfter)
                            <span></span>
                        @endif
                    @endforeach
                </div>
                @foreach($rows as $rowNumber => $rowSeats)
                    @php $byLetter = $rowSeats->keyBy('letter'); @endphp
                    <div class="grid items-center gap-1" style="grid-template-columns: 2rem repeat({{ count($letters) + 1 }}, minmax(1.8rem, 1fr));">
                        <span class="text-[11px] text-white/50 text-right pr-1">{{ $rowNumber }}</span>
                        @foreach($letters as $letter)
                            @php $seat = $byLetter->get($letter); @endphp
                            @if($seat)
                                <button type="button"
                                        class="h-8 rounded-md text-[11px] font-semibold transition"
                                        :disabled="{{ $seat['available'] ? 'false' : 'true' }}"
                                        @click="pickSeat('{{ $seat['designator'] }}')"
                                        :class="seatClass('{{ $seat['designator'] }}', {{ $seat['available'] ? 'true' : 'false' }})"
                                        title="{{ $seat['designator'] }}{{ $seat['available'] && $seat['amount'] > 0 ? ' · extra '.$seat['amount'] : '' }}">
                                    {{ $seat['available'] ? $letter : '×' }}
                                </button>
                            @else
                                <span class="h-8"></span>
                            @endif
                            @if($letter === $aisleAfter)
                                <span class="h-8"></span>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap gap-4 text-[11px] text-white/70">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-white/90"></span> Available</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-gold-500"></span> Selected</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-white/20"></span> Taken</span>
            </div>
        </div>
    </div>
</div>
@endif
