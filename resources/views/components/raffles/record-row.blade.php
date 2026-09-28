@props([
    'raffle',
    'projection',
])

{{-- A record in the history. The whole row opens it; the PDF has its own button. --}}
@php
    $winners = $raffle->publicWinners($projection);
    $first = $winners->first();
    $others = $raffle->winners_count - 1;
@endphp

<div {{ $attributes->class('relative flex items-center gap-4 px-5 py-3.5 transition hover:bg-zinc-50/80 dark:hover:bg-white/3') }}>
    <span class="w-14 shrink-0 font-display text-sm font-semibold text-zinc-400 tabular-nums max-sm:hidden dark:text-zinc-500">
        {{ __('No. :number', ['number' => $raffle->id]) }}
    </span>

    @if ($first)
        <flux:avatar size="sm" :name="$first->name" />
    @else
        <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-dashed border-gold-400/60 text-gold-500">
            <flux:icon.question-mark-circle variant="micro" class="size-4" />
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <a href="{{ route('raffles.show', $raffle) }}" class="block truncate text-sm font-semibold text-zinc-900 after:absolute after:inset-0 dark:text-white" wire:navigate>
            @if ($first)
                {{ $others > 0 ? __(':name and :count more', ['name' => $first->name, 'count' => $others]) : $first->name }}
            @else
                {{ trans_choice('{1} Winner to be revealed|[2,*] :count winners to be revealed', $raffle->winners_count) }}
            @endif
        </a>
        <div class="flex items-center gap-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
            <flux:icon.gift variant="micro" class="size-3 shrink-0 text-gold-600 dark:text-gold-400" />
            <span class="truncate">
                {{ $raffle->prize }}
                @if ($first && $others === 0)
                    · {{ $first->school->name }}, {{ $first->school->city->name }}
                @endif
            </span>
        </div>
    </div>

    <div class="hidden max-w-72 min-w-0 text-right lg:block">
        <div class="truncate text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $raffle->filter_description }}</div>
        <div class="truncate text-xs text-zinc-400 dark:text-zinc-500">
            {{ trans_choice('{1} :count participant|[2,*] :count participants', $raffle->participants_count) }} · {{ $raffle->animation->label() }}
            @if ($raffle->assembly)
                · {{ $raffle->assembly->name }}
            @endif
        </div>
    </div>

    <div class="flex shrink-0 flex-col items-end gap-1">
        @if ($winners->count() < $raffle->winners_count)
            <x-raffles.on-screen-badge />
        @else
            <x-raffles.no-quorum-badge :raffle="$raffle" />
        @endif
        <span class="text-xs text-zinc-400 dark:text-zinc-500">
            {{ $raffle->drawn_at->isToday() ? $raffle->drawn_at->diffForHumans() : $raffle->drawn_at->translatedFormat('j M Y') }}
        </span>
    </div>

    <x-raffles.download-button :raffle="$raffle" :projection="$projection" compact class="relative z-10" />
</div>
