@props([
    'size' => 40,
    'surface' => 'dark',
])

{{-- Mark + name. "surface" is the background it sits on: dark (sidebar, stage) or light (auth forms). --}}
<div {{ $attributes->class('flex items-center gap-3') }}>
    <x-brand.mark :size="$size" />

    <div class="leading-none">
        <div @class([
            'font-display text-[1.35rem] font-semibold tracking-[0.16em]',
            'text-white' => $surface === 'dark',
            'text-brand-900 dark:text-white' => $surface === 'light',
        ])>
            SIMAC
        </div>
        <div @class([
            'mt-1.5 text-[0.66rem] font-semibold tracking-[0.28em] uppercase',
            'text-gold-300/80' => $surface === 'dark',
            'text-gold-600 dark:text-gold-300/80' => $surface === 'light',
        ])>
            {{ __('Raffles') }}
        </div>
    </div>
</div>
