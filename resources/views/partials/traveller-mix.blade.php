@php
    $mix = $mix ?? \App\Support\PassengerMix::fromRequest(request());
    $wrapClass = $wrapClass ?? '';
    $labelClass = $labelClass ?? 'block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1';
    $buttonClass = $buttonClass ?? 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-left focus:outline-none focus:ring-2 focus:ring-gold-500';
@endphp
<div class="{{ $wrapClass }} relative" x-data="travellerMix({
    adults: {{ (int) $mix->adults }},
    children: {{ (int) $mix->children }},
    infants: {{ (int) $mix->infants }}
})" @click.outside="open = false">
    <label class="{{ $labelClass }}">Travellers</label>
    <button type="button" class="{{ $buttonClass }}" @click="open = !open">
        <span x-text="summary()"></span>
    </button>
    <input type="hidden" name="adults" :value="adults">
    <input type="hidden" name="children" :value="children">
    <input type="hidden" name="infants" :value="infants">
    <input type="hidden" name="passengers" :value="total()">
    <div x-cloak x-show="open" x-transition
         class="absolute z-30 mt-2 w-72 rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Adults</p>
                    <p class="text-xs text-slate-400">12+ years</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="dec('adults')">−</button>
                    <span class="w-5 text-center font-semibold" x-text="adults"></span>
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="inc('adults')">+</button>
                </div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Children</p>
                    <p class="text-xs text-slate-400">2–11 years</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="dec('children')">−</button>
                    <span class="w-5 text-center font-semibold" x-text="children"></span>
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="inc('children')">+</button>
                </div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Infants</p>
                    <p class="text-xs text-slate-400">Under 2 years, on lap</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="dec('infants')">−</button>
                    <span class="w-5 text-center font-semibold" x-text="infants"></span>
                    <button type="button" class="h-8 w-8 rounded-full border border-slate-200 font-semibold" @click="inc('infants')">+</button>
                </div>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-400">Infants must travel with an adult. Maximum 9 travellers.</p>
        <button type="button" class="mt-3 w-full rounded-full bg-gold-500 text-navy-950 text-sm font-semibold py-2" @click="open = false">Done</button>
    </div>
</div>
