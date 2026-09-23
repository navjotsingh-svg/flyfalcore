@php
    $wrapClass = $wrapClass ?? '';
    $buttonClass = $buttonClass ?? 'rounded-full px-4 py-1.5 text-sm font-semibold transition';
@endphp
<div class="flex flex-wrap items-center gap-2 {{ $wrapClass }}">
    <button type="button"
            class="{{ $buttonClass }}"
            :class="trip === 'oneway' ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            @click="trip = 'oneway'">One way</button>
    <button type="button"
            class="{{ $buttonClass }}"
            :class="trip === 'return' ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            @click="trip = 'return'">Return</button>
</div>
