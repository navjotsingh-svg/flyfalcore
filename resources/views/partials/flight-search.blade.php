@php
    $field = 'w-full h-9 bg-transparent border-0 px-0 py-0 text-[15px] font-semibold text-navy-900 placeholder:text-slate-500 placeholder:font-semibold focus:outline-none focus:ring-0';
    $chip = 'block h-4 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400 mb-1.5 leading-none';
    $cell = 'min-w-0 h-full rounded-2xl bg-white px-3.5 py-3 ring-1 ring-slate-200/90 focus-within:ring-2 focus-within:ring-gold-500';
@endphp
<div class="home-search relative rounded-[28px] bg-white shadow-[0_28px_80px_-28px_rgba(7,18,34,0.55)] ring-1 ring-white/70">
    <!-- <div class="h-1.5 rounded-t-[28px] bg-gradient-to-r from-gold-600 via-gold-400 to-gold-500"></div> -->
    <form action="{{ route('flights.index') }}" method="GET"
          class="p-4 sm:p-6 lg:p-7"
          x-data="airportPair({
              url: @js(route('airports.suggest')),
              trip: 'oneway',
              depart: @js(now()->addDay()->toDateString()),
              cabin: 'economy',
          })"
          @submit="if (!validate()) $event.preventDefault()">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="h-9 w-9 shrink-0 rounded-full bg-navy-950 text-gold-400 inline-flex items-center justify-center">
                    <i class="fa-solid fa-plane-up text-sm"></i>
                </span>
                <div class="min-w-0">
                    <p class="font-display text-xl font-bold text-navy-900 leading-none">Search flights</p>
                    <p class="mt-1 text-xs font-medium text-gold-600">Live fares · Air travel services</p>
                </div>
            </div>
            @include('partials.trip-type', [
                'wrapClass' => 'shrink-0',
                'buttonClass' => 'rounded-full px-4 py-2 text-sm font-semibold transition min-w-[6.25rem] text-center',
            ])
        </div>

        <div class="mt-4">
            @include('partials.cabin-class-picker', ['compact' => true])
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-2.5 items-stretch">
            <div class="relative sm:col-span-2 xl:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-12">
                @include('partials.airport-suggest', [
                    'fromWrap' => $cell,
                    'toWrap' => $cell,
                    'swapWrap' => 'flex justify-center -my-1 sm:my-0 sm:absolute sm:z-10 sm:left-1/2 sm:top-[3.25rem] sm:-translate-x-1/2 sm:-translate-y-1/2',
                    'swapButtonClass' => 'h-8 w-8 rounded-full bg-navy-950 text-gold-400 shadow-md ring-2 ring-white hover:bg-navy-900 transition inline-flex items-center justify-center',
                    'fromIcon' => 'fa-plane-departure',
                    'toIcon' => 'fa-plane-arrival',
                    'iconPosition' => 'left-0',
                    'iconPadClass' => 'pl-7',
                    'toIconPosition' => 'left-1',
                    'toIconPadClass' => 'pl-8',
                    'inputClass' => $field,
                    'labelClass' => $chip,
                ])
            </div>

            <div class="{{ $cell }}" :class="trip === 'return' ? 'xl:col-span-2' : 'xl:col-span-3'">
                <label class="{{ $chip }}">Departure</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2 text-gold-600"><i class="fa-regular fa-calendar"></i></span>
                    <input type="date" name="date" x-model="depart" min="{{ now()->toDateString() }}" required
                           class="{{ $field }} pl-7">
                </div>
            </div>

            <div class="{{ $cell }} xl:col-span-2" x-show="trip === 'return'" x-cloak>
                <label class="{{ $chip }}">Return</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-0 top-1/2 -translate-y-1/2 text-gold-600"><i class="fa-regular fa-calendar"></i></span>
                    <input type="date" name="return_date" value="{{ now()->addDays(8)->toDateString() }}"
                           :min="depart || '{{ now()->toDateString() }}'"
                           :required="trip === 'return'"
                           :disabled="trip !== 'return'"
                           class="{{ $field }} pl-7">
                </div>
            </div>

            <div class="min-w-0" :class="trip === 'return' ? 'xl:col-span-2' : 'xl:col-span-3'">
                @include('partials.traveller-mix', [
                    'wrapClass' => $cell,
                    'labelClass' => $chip,
                    'buttonClass' => $field.' pl-7 text-left',
                    'icon' => 'fa-user-group',
                    'iconPosition' => 'left-0',
                ])
            </div>
        </div>

        <div class="mt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-slate-500 sm:pl-0.5">
                <i class="fa-solid fa-shield-halved text-gold-600 mr-1"></i>
                Compare live airline fares. Pay securely at checkout.
            </p>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-7 py-3.5 shadow-[0_12px_24px_-12px_rgba(176,139,63,0.9)] transition w-full sm:w-auto">
                <i class="fa-solid fa-magnifying-glass"></i>
                Search flights
            </button>
        </div>
    </form>
</div>
