@props([
    'name',
    'attempt',
])

{{-- 3, 2, 1 inside a gold ring that empties, then the name letter by letter. --}}
<div x-data="reveal(@js(['name' => $name, 'attempt' => $attempt]))" class="relative flex min-h-[340px] w-full flex-col items-center justify-center px-4 text-center">
    <template x-if="count > 0">
        <div class="relative flex items-center justify-center">
            <div class="absolute size-72 rounded-full bg-gold-400/15 blur-3xl"></div>

            <svg width="300" height="300" viewBox="0 0 300 300" class="absolute -rotate-90" aria-hidden="true">
                <circle cx="150" cy="150" r="132" fill="none" stroke="rgb(255 255 255 / 0.08)" stroke-width="4" />
                <circle
                    cx="150"
                    cy="150"
                    r="132"
                    fill="none"
                    stroke="#f2b72f"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke-dasharray="829.4"
                    class="transition-[stroke-dashoffset] duration-1000 ease-linear"
                    :style="`stroke-dashoffset: ${829.4 * (1 - count / 3)}`"
                />
            </svg>

            {{-- A new element per number, so the beat plays each time. --}}
            <template x-for="number in [count]" :key="number">
                <span class="relative animate-beat font-display font-semibold text-gold-300 tabular-nums [font-size:min(26vmin,170px)] [text-shadow:0_0_90px_rgb(242_183_47/0.45)]" x-text="number"></span>
            </template>
        </div>
    </template>

    <template x-if="count === 0">
        <div>
            <div class="mb-5 animate-fade-in text-[0.72rem] font-semibold tracking-[0.5em] text-gold-300/70 uppercase">{{ __('And the prize goes to') }}</div>

            <div class="min-h-[1.3em] font-display leading-[1.15] font-semibold text-white [font-size:min(8.5vmin,82px)]">
                <span
                    x-text="shown"
                    :class="complete && 'bg-[linear-gradient(100deg,#ffffff_20%,#f9df8f_42%,#f2b72f_52%,#ffffff_72%)] bg-[length:220%_100%] bg-clip-text text-transparent [animation:sweep_2.6s_ease-out_forwards]'"
                ></span>
                <span x-show="! complete" class="animate-breathe text-gold-400">▌</span>
            </div>
        </div>
    </template>
</div>
