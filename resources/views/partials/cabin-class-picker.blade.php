@php
    $includeAny = $includeAny ?? false;
    $options = \App\Support\AirlineCopy::cabinOptions($includeAny);
    $wrapClass = $wrapClass ?? '';
@endphp
<div class="{{ $wrapClass }}">
    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Class</p>
    <div class="grid grid-cols-2 {{ $includeAny ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-2">
        @foreach($options as $option)
            <label class="cursor-pointer">
                <input type="radio" name="cabin" value="{{ $option['value'] }}" class="sr-only" x-model="cabin">
                <span class="flex items-start gap-2.5 rounded-2xl border px-3 py-2.5 h-full transition"
                      :class="cabin === @js($option['value']) ? 'border-gold-500 bg-gold-50 shadow-sm' : 'border-slate-200 bg-slate-50 hover:border-gold-300'">
                    <span class="mt-0.5 h-8 w-8 shrink-0 rounded-xl inline-flex items-center justify-center text-sm"
                          :class="cabin === @js($option['value']) ? 'bg-navy-950 text-gold-400' : 'bg-white text-navy-900 ring-1 ring-slate-200'">
                        <i class="fa-solid {{ $option['icon'] }}"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-navy-900 leading-tight">{{ $option['label'] }}</span>
                        <span class="block mt-0.5 text-[11px] text-slate-500">{{ $option['hint'] }}</span>
                    </span>
                </span>
            </label>
        @endforeach
    </div>
</div>
