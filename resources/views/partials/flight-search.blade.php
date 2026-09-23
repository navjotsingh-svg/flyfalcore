<div class="bg-white rounded-2xl shadow-search border border-slate-100 p-4 sm:p-6">
    <div class="flex items-center gap-4 pb-4 text-sm font-semibold">
        <span class="text-navy-900">Search flights</span>
        <span class="text-xs font-medium text-gold-600">Air travel services</span>
    </div>
    <form action="{{ route('flights.index') }}" method="GET"
          class="space-y-3"
          x-data="airportPair({
              url: @js(route('airports.suggest')),
              trip: 'return',
              depart: @js(now()->addDay()->toDateString()),
              cabin: 'economy',
          })"
          @submit="if (!validate()) $event.preventDefault()">
        @include('partials.trip-type')
        @include('partials.cabin-class-picker')
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            @include('partials.airport-suggest', [
                'fromWrap' => 'md:col-span-2',
                'toWrap' => 'md:col-span-2',
            ])
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Departure</label>
                <input type="date" name="date" x-model="depart" min="{{ now()->toDateString() }}" required
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500">
            </div>
            
            <div class="md:col-span-2" x-show="trip === 'return'" x-cloak>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Return</label>
                <input type="date" name="return_date" value="{{ now()->addDays(8)->toDateString() }}"
                       :min="depart || '{{ now()->toDateString() }}'"
                       :required="trip === 'return'"
                       :disabled="trip !== 'return'"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500">
            </div>
            @include('partials.traveller-mix', ['wrapClass' => 'md:col-span-2'])
            <div class="md:col-span-1">
                <button type="submit" class="h-12 w-full rounded-xl bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold flex items-center justify-center transition" aria-label="Search flights">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
    </form>
</div>
