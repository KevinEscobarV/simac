@php
    $revealed = $this->projection->revealedOf($raffle);
    $hidden = $raffle->winners_count - $revealed;
    $several = $raffle->winners_count > 1;

    $quorum = match (true) {
        $raffle->assembly === null => __('There was no assembly open'),
        $raffle->quorum_met === null => __('The assembly did not require quorum'),
        $raffle->quorum_met => __('Met when drawing'),
        default => __('Not met when drawing'),
    };
@endphp

<div>
    <x-page-header
        :title="__('Record No. :number', ['number' => $raffle->id])"
        :description="Str::ucfirst($raffle->drawn_at->translatedFormat('l j \d\e F \d\e Y, g:i a'))"
    >
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('raffles.index')" wire:navigate>{{ __('History') }}</flux:button>
            <x-raffles.download-button :raffle="$raffle" :projection="$this->projection" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid items-start gap-5 lg:grid-cols-3">
        <div class="min-w-0 space-y-5 lg:col-span-2">
            {{-- Winners --}}
            <section class="dark relative overflow-hidden rounded-2xl border border-brand-800 bg-institutional shadow-raised">
                <div class="pointer-events-none absolute -top-24 right-8 size-64 rounded-full bg-gold-500/16 blur-3xl"></div>

                <div class="relative p-6 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:icon.gift variant="micro" class="size-4 text-gold-300" />
                        <span class="text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('Prize') }}</span>
                        @if ($hidden > 0)
                            <x-raffles.on-screen-badge />
                        @endif
                    </div>
                    <h2 class="mt-1.5 font-display text-2xl leading-tight font-semibold text-balance text-white sm:text-3xl">{{ $raffle->prize }}</h2>

                    <div class="mt-6 mb-2 text-[0.66rem] font-semibold tracking-[0.2em] text-white/45 uppercase">
                        {{ $several ? __('Winners') : __('Winner') }}
                    </div>

                    <ol class="divide-y divide-white/8 overflow-hidden rounded-xl border border-white/10 bg-white/5">
                        @foreach ($this->winners as $winner)
                            <li wire:key="winner-{{ $winner->id }}" class="flex items-center gap-3.5 px-4 py-3">
                                @if ($several)
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gold-400/20 font-display text-sm font-semibold text-gold-200">{{ $winner->pivot->winner_position }}</span>
                                @endif
                                <flux:avatar :name="$winner->name" class="ring-2 ring-gold-300/50 max-sm:hidden" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-display text-lg leading-tight font-semibold text-white">{{ $winner->name }}</div>
                                    <div class="truncate text-xs text-white/55">{{ $winner->place }}</div>
                                </div>
                                <span class="shrink-0 rounded-md border border-white/12 bg-white/8 px-2 py-1 text-xs font-semibold tracking-wide text-white/70 tabular-nums">{{ $winner->code }}</span>
                            </li>
                        @endforeach

                        @for ($position = $revealed + 1; $position <= $raffle->winners_count; $position++)
                            <li wire:key="hidden-{{ $position }}" class="flex items-center gap-3.5 px-4 py-3">
                                @if ($several)
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-dashed border-gold-300/40 font-display text-sm font-semibold text-gold-200/70">{{ $position }}</span>
                                @endif
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-dashed border-gold-300/40 text-gold-200/70 max-sm:hidden">
                                    <flux:icon.question-mark-circle variant="mini" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-white/70">{{ __('To be revealed') }}</div>
                                    <div class="text-xs text-white/40">{{ __('It shows up here as soon as the screen reveals it.') }}</div>
                                </div>
                            </li>
                        @endfor
                    </ol>
                </div>
            </section>

            {{-- Participants --}}
            <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-3.5 dark:border-white/10">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Participants') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice('{1} :count teacher entered the draw|[2,*] :count teachers entered the draw', $raffle->participants_count) }}
                        </p>
                    </div>

                    <div class="w-full sm:w-64">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            size="sm"
                            icon="magnifying-glass"
                            :placeholder="__('Name, code or school…')"
                            :aria-label="__('Search participants')"
                            clearable
                        />
                    </div>
                </div>

                @if ($participants->isEmpty())
                    <x-empty-state icon="magnifying-glass" :title="__('No matches')" :message="__('Nobody in this raffle matches the search.')" />
                @else
                    <div class="px-5">
                        <flux:table :paginate="$participants" pagination:class="py-3" wire:loading.delay.class="opacity-60" class="transition-opacity">
                            <flux:table.columns>
                                <flux:table.column>{{ __('Teacher') }}</flux:table.column>
                                <flux:table.column class="max-sm:hidden">{{ __('Code') }}</flux:table.column>
                                <flux:table.column class="max-md:hidden">{{ __('Municipality') }}</flux:table.column>
                                <flux:table.column align="end"><span class="sr-only">{{ __('Result') }}</span></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($participants as $participant)
                                    @php
                                        $position = $participant->pivot->winner_position;
                                        $won = $position !== null && $position <= $revealed;
                                    @endphp

                                    <flux:table.row :key="$participant->id">
                                        <flux:table.cell>
                                            <div class="flex items-center gap-3">
                                                <flux:avatar size="xs" :name="$participant->name" />
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $participant->name }}</div>
                                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $participant->school?->name ?? $participant->city->name }}</div>
                                                </div>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell class="text-xs font-semibold tracking-wide text-zinc-500 tabular-nums max-sm:hidden dark:text-zinc-400">{{ $participant->code }}</flux:table.cell>
                                        <flux:table.cell class="max-md:hidden">{{ $participant->city->name }}</flux:table.cell>
                                        <flux:table.cell align="end">
                                            @if ($won)
                                                <flux:badge size="sm" color="amber" icon="trophy">
                                                    {{ $several ? __('Winner :position', ['position' => $position]) : __('Winner') }}
                                                </flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="min-w-0 space-y-5">
            {{-- The record --}}
            <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('The record') }}</h2>

                <dl class="mt-4 space-y-3.5 text-sm">
                    @foreach ([
                        __('Assembly') => $raffle->assembly ? $raffle->assembly->name.' · '.$raffle->assembly->date->translatedFormat('j M Y') : __('None open'),
                        __('Who took part') => $raffle->filter_description,
                        __('Participants') => number_format($raffle->participants_count, 0, ',', '.'),
                        $several ? __('Winners') : __('Winner') => $several ? $raffle->winners_count : 1,
                        __('Animation') => $raffle->animation->label(),
                        __('Drawn by') => $raffle->drawer?->name ?? '—',
                    ] as $term => $detail)
                        <div>
                            <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $term }}</dt>
                            <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $detail }}</dd>
                        </div>
                    @endforeach

                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Quorum') }}</dt>
                        <dd class="mt-1">
                            @if ($raffle->quorum_met === false)
                                <x-raffles.no-quorum-badge :raffle="$raffle" />
                                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $quorum }}</span>
                            @elseif ($raffle->quorum_met)
                                <flux:badge size="sm" color="green" icon="check-circle">{{ $quorum }}</flux:badge>
                            @else
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $quorum }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- How it was drawn --}}
            <section class="rounded-2xl border border-brand-200/70 bg-brand-50/60 p-5 dark:border-brand-500/20 dark:bg-brand-500/8">
                <div class="flex items-center gap-2 text-brand-800 dark:text-brand-200">
                    <flux:icon.shield-check variant="mini" class="size-4.5" />
                    <h2 class="text-sm font-semibold">{{ __('How it was drawn') }}</h2>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-brand-900/75 dark:text-brand-100/70">
                    {{ __('The server chose the winners with the system\'s cryptographic generator, over the list of participants in the database. The record was saved before the animation: the screen only showed a result already sealed.') }}
                </p>
            </section>
        </aside>
    </div>
</div>
