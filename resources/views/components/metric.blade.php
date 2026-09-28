@props([
    'icon',
    'label',
    'value',
    'hint' => null,
    'tone' => 'zinc',
])

@php
    $chip = match ($tone) {
        'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'gold' => 'bg-gold-50 text-gold-700 dark:bg-gold-400/10 dark:text-gold-300',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-white/5 dark:text-zinc-300',
    };
@endphp

<div {{ $attributes->class('rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-card dark:border-white/10 dark:bg-zinc-900') }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="font-display text-3xl leading-none font-semibold text-zinc-900 tabular-nums dark:text-white">
                {{ number_format($value, 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</div>

            @if ($hint)
                <div class="mt-0.5 text-[0.7rem] text-zinc-400 dark:text-zinc-500">{{ $hint }}</div>
            @endif
        </div>

        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $chip }}">
            <flux:icon :icon="$icon" variant="mini" class="size-4.5" />
        </span>
    </div>
</div>
