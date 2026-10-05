@props([
    'title' => null,
    'subtitle' => null,
    'image' => null,
])

{{-- The event the union is holding, on the home page (and as its preview in Configuration). --}}
<section {{ $attributes->class('dark relative overflow-hidden rounded-2xl border border-brand-800 bg-institutional text-white shadow-raised') }}>
    <div class="pointer-events-none absolute -right-10 -bottom-28 size-72 rounded-full bg-gold-500/16 blur-3xl"></div>

    <div class="relative flex items-center gap-5 px-6 py-5 sm:px-7">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">
                <flux:icon.sparkles variant="micro" class="size-3.5" />
                {{ __('Event') }}
            </div>

            @if (filled($title))
                <h2 class="mt-2 font-display text-2xl leading-tight font-semibold text-balance sm:text-3xl">{{ $title }}</h2>
            @endif

            @if (filled($subtitle))
                <p class="mt-1.5 text-sm text-white/65 sm:text-base">{{ $subtitle }}</p>
            @endif
        </div>

        @if (filled($image))
            <img
                src="{{ $image }}"
                alt="{{ filled($title) ? '' : __('Event image') }}"
                class="-my-2 h-28 w-auto max-w-[40%] shrink-0 object-contain drop-shadow-[0_12px_24px_rgba(0,0,0,0.45)] sm:h-36"
            >
        @endif
    </div>
</section>
