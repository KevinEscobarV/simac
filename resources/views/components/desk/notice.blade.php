@props([
    'tone' => 'notice',
    'title',
    'name' => null,
    'detail' => null,
    'undoable' => false,
    'dismiss' => null,
])

{{--
    The big confirmation at the desk: what the person at the door looks at
    before saying "go ahead". Given the id to dismiss, it fades out on its own
    after six seconds; nobody in front of a queue has time to close it.
--}}
@php
    [$box, $chip, $heading, $bar, $icon] = match ($tone) {
        'in' => [
            'border-brand-300 bg-brand-50 dark:border-brand-500/40 dark:bg-brand-500/10',
            'bg-brand-600 text-white',
            'text-brand-800 dark:text-brand-200',
            'bg-brand-500/50',
            'check',
        ],
        'out' => [
            'border-gold-300 bg-gold-50 dark:border-gold-400/35 dark:bg-gold-400/10',
            'bg-gold-400 text-brand-950',
            'text-gold-800 dark:text-gold-200',
            'bg-gold-400/60',
            'arrow-right-start-on-rectangle',
        ],
        'error' => [
            'border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10',
            'bg-red-500 text-white',
            'text-red-700 dark:text-red-300',
            'bg-red-400/50',
            'exclamation-triangle',
        ],
        default => [
            'border-zinc-300 bg-white dark:border-white/15 dark:bg-zinc-900',
            'bg-zinc-200 text-zinc-700 dark:bg-white/10 dark:text-zinc-200',
            'text-zinc-800 dark:text-white',
            'bg-zinc-300 dark:bg-white/20',
            'information-circle',
        ],
    };
@endphp

<div
    @if ($dismiss !== null)
        x-data="{ leaving: false }"
        x-init="setTimeout(() => { leaving = true; setTimeout(() => $wire.dismiss({{ $dismiss }}), 300) }, 6000)"
        x-bind:class="leaving && 'opacity-0 -translate-y-1'"
    @endif
    {{ $attributes->class(['relative flex animate-pop flex-wrap items-center gap-x-4 gap-y-3 overflow-hidden rounded-2xl border p-4 shadow-card transition duration-300', $box]) }}
>
    <div class="flex min-w-0 flex-1 basis-60 items-center gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $chip }}">
            <flux:icon :icon="$icon" class="size-6" />
        </span>

        <div class="min-w-0 flex-1">
            <div class="font-display text-lg leading-tight font-semibold {{ $heading }}">{{ $title }}</div>
            <div class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">
                @if ($name)
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $name }}</span>
                    @if ($detail)
                        ·
                    @endif
                @endif
                {{ $detail }}
            </div>
        </div>
    </div>

    @if ($undoable || $dismiss !== null)
        <div class="flex shrink-0 items-center gap-1 max-sm:ms-auto">
            @if ($undoable)
                <flux:button size="sm" icon="arrow-uturn-left" wire:click="undo">{{ __('Undo') }}</flux:button>
            @endif

            @if ($dismiss !== null)
                <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="dismiss({{ $dismiss }})" :aria-label="__('Close')" />
            @endif
        </div>
    @endif

    @if ($dismiss !== null)
        <span class="absolute inset-x-0 bottom-0 h-1 origin-left animate-drain {{ $bar }}" aria-hidden="true"></span>
    @endif
</div>
