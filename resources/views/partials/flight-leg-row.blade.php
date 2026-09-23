@php
    $selectable = $selectable ?? false;
    $selected = $selected ?? false;
@endphp
<div class="flex items-center gap-3 min-w-0">
    @if($selectable)
        <span class="shrink-0 h-4 w-4 rounded-full border-2 {{ $selected ? 'border-gold-500 bg-gold-500' : 'border-slate-300 bg-white' }} inline-flex items-center justify-center">
            @if($selected)<span class="h-1.5 w-1.5 rounded-full bg-navy-950"></span>@endif
        </span>
    @endif
    <div class="h-9 w-9 shrink-0 rounded-lg bg-navy-950 text-gold-400 inline-flex items-center justify-center text-[11px] font-bold tracking-wide">
        {{ $leg['initials'] ?? 'F' }}
    </div>
    <div class="w-[5.75rem] sm:w-28 shrink-0 min-w-0">
        <p class="text-sm font-semibold text-navy-900 truncate">{{ $leg['airline'] }}</p>
        <p class="text-[11px] text-slate-500 truncate">{{ $leg['flight_number'] }}</p>
    </div>
    <div class="flex-1 grid grid-cols-[1fr_minmax(4.5rem,7.5rem)_1fr] items-center gap-1.5 min-w-0">
        <div>
            <p class="text-lg sm:text-xl font-extrabold text-navy-900 leading-none tabular-nums">{{ $leg['depart_time'] }}</p>
            <p class="mt-1 text-xs font-semibold text-navy-900">{{ $leg['origin_code'] }}</p>
            <p class="text-[11px] text-slate-400 truncate">{{ $leg['depart_date'] }}</p>
        </div>
        <div class="text-center px-1">
            <p class="text-[10px] font-semibold text-slate-400">{{ $leg['duration'] }}</p>
            <div class="my-1 flex items-center">
                <span class="h-1.5 w-1.5 rounded-full bg-navy-900"></span>
                <span class="flex-1 border-t border-slate-300"></span>
                <span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span>
            </div>
            <p class="text-[10px] font-semibold {{ ($leg['stops'] ?? 0) === 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $leg['stop_label'] }}</p>
        </div>
        <div class="text-right">
            <p class="text-lg sm:text-xl font-extrabold text-navy-900 leading-none tabular-nums">
                {{ $leg['arrive_time'] }}
                @if(($leg['plus_days'] ?? 0) > 0)
                    <sup class="text-gold-600 text-[10px]">+{{ $leg['plus_days'] }}</sup>
                @endif
            </p>
            <p class="mt-1 text-xs font-semibold text-navy-900">{{ $leg['destination_code'] }}</p>
            <p class="text-[11px] text-slate-400 truncate">{{ $leg['arrive_date'] }}</p>
        </div>
    </div>
</div>
