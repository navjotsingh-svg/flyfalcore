<section class="page-hero">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
        <p class="text-xs font-semibold tracking-[0.22em] uppercase text-gold-400">{{ $eyebrow ?? 'Falcore' }}</p>
        <h1 class="mt-4 font-display text-4xl sm:text-6xl font-bold text-white leading-tight max-w-3xl">{!! $title !!}</h1>
        @if(!empty($subtitle))
            <p class="mt-5 max-w-2xl text-white/75 text-lg leading-relaxed">{{ $subtitle }}</p>
        @endif
    </div>
</section>
