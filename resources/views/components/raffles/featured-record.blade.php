@props([
    'raffle',
    'projection',
])

{{-- The latest record, on a dark card: who won, what, and how it was drawn. --}}
@php
    $winners = $raffle->publicWinners($projection);
    $hidden = $raffle->winners_count - $winners->count();
    // Up to three places: the public winners first, then the ones still to come.
    $places = min(3, $raffle->winners_count);
    $toCome = $winners->count() < $places ? range($winners->count() + 1, $places) : [];
@endphp

<article {{ $attributes->class('dark relative overflow-hidden rounded-2xl border border-brand-800 bg-institutional shadow-raised') }}>
    <div class="pointer-events-none absolute -top-24 right-8 size-64 rounded-full bg-gold-500/16 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-12 size-64 rounded-full bg-brand-500/22 blur-3xl"></div>

    <div class="relative flex gap-6 p-6 sm:p-7">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <flux:icon.trophy variant="micro" class="size-4 text-gold-300" />
                <span class="text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">
                    {{ __('Latest raffle') }} · {{ $raffle->drawn_at->diffForHumans() }}
                </span>
                @if ($hidden > 0)
                    <x-raffles.on-screen-badge />
                @endif
            </div>

            <h2 class="mt-2 font-display text-2xl leading-tight font-semibold text-balance text-white sm:text-3xl">
                {{ $raffle->prize }}
            </h2>

            <ul class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($winners->take($places) as $winner)
                    <li wire:key="featured-winner-{{ $winner->id }}" class="flex min-w-0 items-center gap-3">
                        <div class="relative shrink-0">
                            <div class="absolute inset-0 rounded-full bg-gold-400/25 blur-md"></div>
                            <flux:avatar :name="$winner->name" class="relative ring-2 ring-gold-300/60" />
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-white">{{ $winner->name }}</div>
                            <div class="truncate text-xs text-white/55">{{ $winner->school->name }} · {{ $winner->school->city->name }}</div>
                        </div>
                    </li>
                @endforeach

                @foreach ($toCome as $position)
                    <li wire:key="featured-hidden-{{ $position }}" class="flex min-w-0 items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-dashed border-gold-300/40 text-gold-200/70">
                            <flux:icon.question-mark-circle variant="mini" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-white/70">{{ __('To be revealed') }}</div>
                            <div class="truncate text-xs text-white/40">
                                {{ $raffle->winners_count > 1 ? __('Winner :position of :total', ['position' => $position, 'total' => $raffle->winners_count]) : __('The screen shows it') }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($raffle->winners_count > $places)
                <a href="{{ route('raffles.show', $raffle) }}" class="mt-3 inline-block text-xs font-medium text-gold-200/80 hover:text-gold-200" wire:navigate>
                    {{ trans_choice('{1} And :count more winner, in the record|[2,*] And :count more winners, in the record', $raffle->winners_count - $places) }}
                </a>
            @endif

            <div class="mt-5 flex flex-wrap items-center gap-1.5 text-[0.7rem] font-medium">
                <span class="rounded-md border border-gold-400/30 bg-gold-400/12 px-2 py-1 text-gold-200">{{ __('Record No. :number', ['number' => $raffle->id]) }}</span>
                @if ($raffle->assembly)
                    <span class="inline-flex items-center gap-1 rounded-md border border-brand-400/30 bg-brand-500/20 px-2 py-1 text-brand-100">
                        <flux:icon.calendar-days variant="micro" class="size-3" />
                        {{ $raffle->assembly->name }}
                    </span>
                @endif
                <x-raffles.no-quorum-badge :raffle="$raffle" />
                <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ $raffle->filter_description }}</span>
                <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ trans_choice('{1} :count participant|[2,*] :count participants', $raffle->participants_count) }}</span>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2.5 border-t border-white/10 pt-5">
                <flux:button :href="route('raffles.show', $raffle)" icon="document-text" size="sm" wire:navigate>{{ __('View record') }}</flux:button>
                <x-raffles.download-button :raffle="$raffle" :projection="$projection" compact />
                <span class="ms-auto text-xs text-white/50">{{ Str::ucfirst($raffle->drawn_at->translatedFormat('j \d\e F \d\e Y, g:i a')) }}</span>
            </div>
        </div>

        <x-raffles.animation-illustration :animation="$raffle->animation" :size="68" class="max-sm:hidden" />
    </div>
</article>
