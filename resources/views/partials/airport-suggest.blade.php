@php
    $inputClass = $inputClass ?? 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500';
    $labelClass = $labelClass ?? 'block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1';
@endphp
<div class="relative {{ $fromWrap ?? '' }}" @click.outside="close('from')">
    <label class="{{ $labelClass }}">From</label>
    <input type="hidden" name="from" :value="fromCode">
    <input type="text"
           id="{{ $fromId ?? 'from-airport' }}"
           x-model="fromQuery"
           @input.debounce.200ms="search('from')"
           @focus="open('from')"
           @keydown.arrow-down.prevent="move('from', 1)"
           @keydown.arrow-up.prevent="move('from', -1)"
           @keydown.enter.prevent="chooseActive('from')"
           @keydown.escape="close('from')"
           autocomplete="off"
           placeholder="City or airport"
           class="{{ $inputClass }}">
    <ul x-cloak x-show="fromOpen && fromItems.length"
        @mousedown.prevent
        class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-card">
        <template x-for="(item, index) in fromItems" :key="item.code">
            <li>
                <button type="button"
                        class="w-full px-3 py-2 text-left hover:bg-gold-50"
                        :class="fromActive === index && 'bg-gold-50'"
                        @click="select('from', item)">
                    <span class="font-semibold text-navy-900" x-text="item.code"></span>
                    <span class="block text-xs text-slate-500" x-text="item.city + ' · ' + item.name"></span>
                </button>
            </li>
        </template>
    </ul>
    <p x-cloak x-show="fromError" class="mt-1 text-xs text-red-600">Choose an airport from the suggestions.</p>
</div>

@if($showSwap ?? true)
    <div class="{{ $swapWrap ?? 'hidden md:flex md:col-span-1 items-center justify-center pb-1' }}">
        <button type="button" @click="swap()" class="h-10 w-10 rounded-full border border-slate-200 text-navy-900 hover:bg-gold-50 transition" aria-label="Swap airports">⇄</button>
    </div>
@endif

<div class="relative {{ $toWrap ?? '' }}" @click.outside="close('to')">
    <label class="{{ $labelClass }}">To</label>
    <input type="hidden" name="to" :value="toCode">
    <input type="text"
           id="{{ $toId ?? 'to-airport' }}"
           x-model="toQuery"
           @input.debounce.200ms="search('to')"
           @focus="open('to')"
           @keydown.arrow-down.prevent="move('to', 1)"
           @keydown.arrow-up.prevent="move('to', -1)"
           @keydown.enter.prevent="chooseActive('to')"
           @keydown.escape="close('to')"
           autocomplete="off"
           placeholder="City or airport"
           class="{{ $inputClass }}">
    <ul x-cloak x-show="toOpen && toItems.length"
        @mousedown.prevent
        class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white py-1 shadow-card">
        <template x-for="(item, index) in toItems" :key="item.code">
            <li>
                <button type="button"
                        class="w-full px-3 py-2 text-left hover:bg-gold-50"
                        :class="toActive === index && 'bg-gold-50'"
                        @click="select('to', item)">
                    <span class="font-semibold text-navy-900" x-text="item.code"></span>
                    <span class="block text-xs text-slate-500" x-text="item.city + ' · ' + item.name"></span>
                </button>
            </li>
        </template>
    </ul>
    <p x-cloak x-show="toError" class="mt-1 text-xs text-red-600">Choose an airport from the suggestions.</p>
</div>
