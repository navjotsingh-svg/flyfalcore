@php
    $bookUrl = $offer['source'] === 'duffel'
        ? route('offers.book', $offer['id'])
        : route('bookings.create', array_merge(['flight' => $offer['id']], ($mix ?? \App\Support\PassengerMix::fromArray($filters ?? []))->query()));
    $symbol = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'INR' => '₹'][$offer['currency']] ?? $offer['currency'].' ';
    $cabin = $offer['cabin_label'] ?? \App\Support\AirlineCopy::cabinLabel($offer['cabin_class'] ?? 'economy');
    $via = $offer['via'] ?? [];
    $stops = (int) ($offer['stops'] ?? 0);
    $stopLabel = $stops === 0 ? 'Non-stop' : $stops.' stop'.($stops > 1 ? 's' : '');
    if ($via !== []) {
        $stopLabel .= ' · via '.implode(', ', $via);
    }
    $initials = $offer['airline_code'] ?: collect(explode(' ', $offer['airline']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $plusDays = $offer['departure_at']->copy()->startOfDay()->diffInDays($offer['arrival_at']->copy()->startOfDay());
    $fareHint = $offer['source'] === 'duffel' ? 'Total fare' : 'Per passenger';
@endphp
<article class="group relative overflow-hidden rounded-3xl bg-white shadow-[0_12px_40px_-24px_rgba(11,26,51,0.35)] ring-1 ring-slate-200/80 hover:ring-gold-400/70 hover:shadow-card transition">
    <span class="absolute inset-y-0 left-0 w-1 bg-gold-500 opacity-0 group-hover:opacity-100 transition"></span>

    <div class="p-4 sm:p-5 lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-12 w-12 shrink-0 rounded-2xl bg-navy-950 text-gold-400 inline-flex items-center justify-center font-bold tracking-wide">
                    {{ $initials ?: 'F' }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-navy-900 truncate">{{ $offer['airline'] }}</p>
                    <p class="text-xs text-slate-500">{{ $offer['flight_number'] }}</p>
                </div>
            </div>
            <span class="shrink-0 rounded-full bg-brand-50 text-navy-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide">{{ $cabin }}</span>
        </div>

        <div class="mt-5 grid grid-cols-[1fr_minmax(5.5rem,9rem)_1fr] items-center gap-2 sm:gap-4">
            <div>
                <p class="font-display text-[1.85rem] sm:text-4xl font-bold text-navy-900 leading-none tabular-nums">{{ $offer['departure_at']->format('H:i') }}</p>
                <p class="mt-2 text-sm font-bold tracking-wide text-navy-900">{{ $offer['origin_code'] }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $offer['origin_city'] }}</p>
                <p class="mt-1 text-[11px] text-slate-400">{{ $offer['departure_at']->format('D, j M') }}</p>
            </div>
            <div class="text-center px-1">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $offer['formatted_duration'] }}</p>
                <div class="relative my-2 flex items-center">
                    <span class="h-2 w-2 rounded-full bg-navy-900"></span>
                    <span class="flex-1 border-t border-dashed border-slate-300"></span>
                    <span class="mx-1 h-7 w-7 rounded-full bg-gold-500/15 text-gold-600 inline-flex items-center justify-center">
                        <i class="fa-solid fa-plane text-[10px]"></i>
                    </span>
                    <span class="flex-1 border-t border-dashed border-slate-300"></span>
                    <span class="h-2 w-2 rounded-full bg-gold-500"></span>
                </div>
                <p class="text-[11px] sm:text-xs font-semibold {{ $stops === 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $stopLabel }}</p>
            </div>
            <div class="text-right">
                <p class="font-display text-[1.85rem] sm:text-4xl font-bold text-navy-900 leading-none tabular-nums">
                    {{ $offer['arrival_at']->format('H:i') }}
                    @if($plusDays > 0)
                        <sup class="text-sm font-semibold text-gold-600">+{{ $plusDays }}</sup>
                    @endif
                </p>
                <p class="mt-2 text-sm font-bold tracking-wide text-navy-900">{{ $offer['destination_code'] }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $offer['destination_city'] }}</p>
                <p class="mt-1 text-[11px] text-slate-400">{{ $offer['arrival_at']->format('D, j M') }}</p>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 bg-slate-50/80 px-4 sm:px-5 lg:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 text-[11px] font-medium text-slate-500">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white ring-1 ring-slate-200 px-2.5 py-1">
                <i class="fa-solid fa-chair text-gold-500"></i> Seat selection
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white ring-1 ring-slate-200 px-2.5 py-1">
                <i class="fa-solid fa-suitcase-rolling text-gold-500"></i> Extra bags
            </span>
            @if(!empty($offer['available_seats']))
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white ring-1 ring-slate-200 px-2.5 py-1">
                    {{ $offer['available_seats'] }} seats left
                </span>
            @endif
        </div>
        <div class="flex items-center gap-4 w-full sm:w-auto">
            <div class="flex-1 sm:flex-none sm:text-right">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">{{ $fareHint }}</p>
                <p class="font-display text-2xl sm:text-3xl font-bold text-navy-900 leading-none">{{ $symbol }}{{ number_format($offer['price'], 0) }}</p>
            </div>
            <a href="{{ $bookUrl }}" class="inline-flex shrink-0 rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-5 py-2.5 transition shadow-sm">
                Book Now
            </a>
        </div>
    </div>
</article>
