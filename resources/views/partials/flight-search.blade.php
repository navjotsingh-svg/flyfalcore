<div class="bg-white rounded-2xl shadow-search border border-slate-100 p-4 sm:p-6">
    <div class="flex items-center gap-4 pb-4 text-sm font-semibold">
        <span class="text-navy-900">Search flights</span>
        <span class="text-xs font-medium text-gold-600">Air travel services</span>
    </div>
    <form action="{{ route('flights.index') }}" method="GET"
          class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end"
          x-data="airportPair({ url: @js(route('airports.suggest')) })"
          @submit="if (!validate()) $event.preventDefault()">
        @include('partials.airport-suggest', [
            'fromWrap' => 'md:col-span-2',
            'toWrap' => 'md:col-span-2',
        ])
        <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Departure</label>
            <input type="date" name="date" min="{{ now()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Cabin</label>
            <select name="cabin" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gold-500">
                <option value="economy">Economy</option>
                <option value="premium_economy">Premium economy</option>
                <option value="business">Business</option>
                <option value="first">First</option>
            </select>
        </div>
        @include('partials.traveller-mix', ['wrapClass' => 'md:col-span-2'])
        <div class="md:col-span-1">
            <button type="submit" class="h-12 w-full rounded-xl bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold flex items-center justify-center transition" aria-label="Search flights">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </form>
</div>