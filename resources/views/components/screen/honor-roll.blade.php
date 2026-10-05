@props([
    'winners',
    'raffle',
])

{{-- With several winners the raffle ends here: everyone who won, in the order they came out. --}}
<div {{ $attributes->class('w-full max-w-4xl px-6 text-center') }}>
    <div class="flex items-center justify-center gap-3 text-gold-300">
        <flux:icon.trophy variant="mini" class="size-5" />
        <span class="text-sm font-semibold tracking-[0.42em] uppercase">{{ __('Roll of honor') }}</span>
        <flux:icon.trophy variant="mini" class="size-5" />
    </div>

    <h2 class="mt-3 font-display text-4xl font-semibold text-white sm:text-6xl">{{ $raffle->prize }}</h2>

    <ol @class(['mt-10 grid gap-3 text-start', 'sm:grid-cols-2' => $winners->count() > 4])>
        @foreach ($winners as $winner)
            <li wire:key="honor-{{ $winner->id }}" class="flex animate-rise items-center gap-4 rounded-2xl border border-gold-400/25 bg-gold-400/8 px-5 py-4" style="animation-delay: {{ $loop->index * 120 }}ms">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-gold-400 font-display text-xl font-semibold text-brand-950 shadow-gold">{{ $winner->pivot->winner_position }}</span>
                <div class="min-w-0">
                    <div class="truncate font-display text-2xl font-semibold text-white">{{ $winner->name }}</div>
                    <div class="truncate text-sm text-white/55">{{ $winner->place }}</div>
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-8 text-sm text-white/40">{{ __('Record No. :number', ['number' => $raffle->id]) }}</div>
</div>
