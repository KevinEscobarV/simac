<div
    x-data="stage"
    data-leave-url="{{ route('screen.leave') }}"
    x-on:animation-finished="$wire.finish($event.detail.attempt)"
    :class="controls || 'cursor-none'"
    class="fixed inset-0 flex flex-col overflow-hidden"
>
    @use('App\Enums\ProjectionPhase')
    @use('App\Enums\RaffleAnimation')

    @php
        $projection = $this->projection;
        $raffle = $projection->raffle;
        $phase = $projection->phase;
        $winner = $this->currentWinner;
        $earlier = $this->earlierWinners;
        $reel = $this->reel;
        $winnerIndex = $winner ? (int) collect($reel)->search(fn ($entry) => $entry['id'] === $winner->id) : 0;
        $landed = $phase === ProjectionPhase::Winner;
        $finale = $landed && $raffle && $raffle->winners_count > 1 && ! $projection->hasWinnersLeft();
        $settings = $this->settings;
        $event = $settings->hasEvent();
        $eventImage = $settings->eventImageUrl();
    @endphp

    <x-screen.ambience />

    {{-- The only controls, and they hide when nobody touches the computer. --}}
    <div class="absolute inset-e-5 top-5 z-40 flex items-center gap-2 transition-opacity duration-500" :class="controls ? 'opacity-100' : 'pointer-events-none opacity-0'">
        <button
            type="button"
            x-show="! sound"
            x-on:click.stop="enableSound()"
            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-white/15 bg-white/8 px-3.5 py-2.5 text-sm font-medium text-white/80 backdrop-blur transition hover:bg-white/14"
        >
            <flux:icon.speaker-wave variant="mini" class="size-4.5" />
            {{ __('Turn on sound') }}
        </button>
        <button
            type="button"
            x-on:click="toggleFullscreen()"
            title="{{ __('Full screen (F)') }}"
            aria-label="{{ __('Full screen') }}"
            class="inline-flex cursor-pointer items-center rounded-xl border border-white/15 bg-white/8 p-2.5 text-white/80 backdrop-blur transition hover:bg-white/14"
        >
            <flux:icon.arrows-pointing-out variant="mini" class="size-5" />
        </button>
    </div>

    <div
        x-show="! connected"
        style="display: none"
        role="status"
        class="absolute bottom-5 left-1/2 z-40 -translate-x-1/2 rounded-full border border-red-300/30 bg-red-500/15 px-4 py-2 text-xs text-red-100 backdrop-blur"
    >
        {{ __('Reconnecting with the server…') }}
    </div>

    {{-- Once a raffle is on, the event stays in view, out of the way: its name in a corner, its image to one side. --}}
    @if ($event && $phase !== ProjectionPhase::Idle)
        @if (filled($settings->event_title) || filled($settings->event_subtitle))
            <div class="pointer-events-none absolute top-5 left-6 z-30 max-w-[28vw] animate-fade-in max-md:hidden">
                @if (filled($settings->event_title))
                    <div class="font-display text-xl leading-tight font-semibold text-balance text-white/85 xl:text-2xl">{{ $settings->event_title }}</div>
                @endif
                @if (filled($settings->event_subtitle))
                    <div class="mt-1 text-sm text-gold-200/70">{{ $settings->event_subtitle }}</div>
                @endif
            </div>
        @endif

        @if ($eventImage)
            <img
                src="{{ $eventImage }}"
                alt=""
                @class([
                    'pointer-events-none absolute bottom-0 w-auto animate-fade-in object-contain drop-shadow-[0_24px_48px_rgba(0,0,0,0.55)] max-xl:hidden',
                    'left-[2vw] h-[56vh] max-w-[22vw]' => $phase === ProjectionPhase::Ready,
                    'left-[1.5vw] h-[20vh] max-w-[12vw]' => $phase !== ProjectionPhase::Ready,
                ])
            >
        @endif
    @endif

    <main class="relative flex flex-1 flex-col items-center justify-center gap-7 px-2 py-8 short:gap-4 short:py-5">
        @if ($phase === ProjectionPhase::Idle)
            {{-- At rest: the room arrives, the screen is on and says nothing else. With an event, it is presented: its image beside its name. --}}
            <div @class(['flex animate-fade-in flex-col items-center gap-8 px-6 text-center', 'lg:flex-row lg:gap-16 lg:text-start' => $eventImage])>
                @if ($eventImage)
                    <img src="{{ $eventImage }}" alt="" class="h-[34vh] w-auto max-w-[80vw] shrink-0 object-contain drop-shadow-[0_30px_60px_rgba(0,0,0,0.55)] lg:h-[70vh] lg:max-w-[38vw]">
                @endif

                <div @class(['flex flex-col items-center gap-8', 'lg:items-start' => $eventImage])>
                    @if ($event)
                        <x-brand.logo :size="52" />

                        @if (filled($settings->event_title) || filled($settings->event_subtitle))
                            <div class="max-w-4xl">
                                @if (filled($settings->event_title))
                                    <h1 class="font-display text-5xl leading-[1.05] font-semibold text-balance text-white sm:text-7xl">{{ $settings->event_title }}</h1>
                                @endif
                                @if (filled($settings->event_subtitle))
                                    <div class="mt-4 text-xl text-gold-200/85 sm:text-3xl">{{ $settings->event_subtitle }}</div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="relative">
                            <div class="absolute inset-0 animate-breathe rounded-full bg-gold-400/20 blur-3xl"></div>
                            <x-brand.mark :size="124" class="relative" />
                        </div>

                        <div>
                            <div class="font-display text-5xl font-semibold tracking-[0.3em] text-white sm:text-7xl">SIMAC</div>
                            <div class="mt-3 text-xs font-semibold tracking-[0.42em] text-gold-300/80 uppercase sm:text-sm">{{ __('Raffles') }}</div>
                        </div>
                    @endif

                    @if ($this->assembly)
                        <div class="text-lg text-white/55 sm:text-2xl">
                            <span class="font-display font-semibold text-white/85">{{ $this->assembly->name }}</span>
                            @if ($this->assembly->location)
                                <span class="text-white/35"> · {{ $this->assembly->location }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center gap-3 text-sm text-white/40 sm:text-base">
                        <span class="relative flex size-2.5">
                            <span class="absolute inline-flex size-full animate-breathe rounded-full bg-brand-300"></span>
                            <span class="relative inline-flex size-2.5 rounded-full bg-brand-300/70"></span>
                        </span>
                        {{ __('Waiting for the raffle') }}
                    </div>
                </div>
            </div>
        @elseif ($phase === ProjectionPhase::Ready)
            {{-- Loaded: the room looks up while the order is given. --}}
            <div class="flex animate-pop flex-col items-center gap-7 px-6 text-center">
                <div class="flex items-center gap-3 text-gold-300">
                    <flux:icon.sparkles class="size-5" />
                    <span class="text-xs font-semibold tracking-[0.42em] uppercase sm:text-sm">{{ __('The raffle is about to start') }}</span>
                    <flux:icon.sparkles class="size-5" />
                </div>

                <div class="font-display text-6xl leading-none font-semibold text-white sm:text-8xl">{{ __('GET READY') }}</div>

                <div class="max-w-4xl">
                    <div class="text-sm tracking-[0.3em] text-white/45 uppercase">{{ __('Raffled prize') }}</div>
                    <div class="mt-2 font-display text-3xl font-semibold text-balance text-gold-200 sm:text-5xl">{{ $raffle->prize }}</div>
                </div>

                @if ($settings->screen_shows_participants || $raffle->winners_count > 1)
                    <div class="flex flex-wrap items-baseline justify-center gap-x-8 gap-y-3">
                        @if ($settings->screen_shows_participants)
                            <div class="flex items-baseline gap-3">
                                <span class="font-display text-5xl leading-none font-semibold text-gold-300 tabular-nums sm:text-7xl">{{ number_format($raffle->participants_count, 0, ',', '.') }}</span>
                                <span class="text-base text-white/55 sm:text-xl">{{ trans_choice('{1} participant|[2,*] participants', $raffle->participants_count) }}</span>
                            </div>
                        @endif
                        @if ($raffle->winners_count > 1)
                            <div class="flex items-baseline gap-3">
                                <span class="font-display text-5xl leading-none font-semibold text-gold-300 tabular-nums sm:text-7xl">{{ $raffle->winners_count }}</span>
                                <span class="text-base text-white/55 sm:text-xl">{{ __('winners') }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($settings->screen_shows_filters && filled($this->filterDescription))
                    <span class="rounded-full border border-white/12 bg-white/8 px-4 py-2 text-sm text-white/70 sm:text-base">{{ $this->filterDescription }}</span>
                @endif
            </div>
        @elseif ($winner)
            <div class="flex items-center gap-3 text-sm text-white/55 sm:text-base">
                <flux:icon.gift variant="mini" class="size-4.5 text-gold-300" />
                <span class="font-semibold text-white/80">{{ $raffle->prize }}</span>
                @if ($raffle->winners_count > 1)
                    <span class="rounded-full border border-gold-400/35 bg-gold-400/12 px-2.5 py-0.5 text-xs font-semibold text-gold-200">
                        {{ __(':position of :total', ['position' => $projection->winner_position, 'total' => $raffle->winners_count]) }}
                    </span>
                @endif
            </div>

            {{-- A new attempt is a new element: the animation starts over. --}}
            <div wire:key="animation-{{ $projection->attempt }}" class="flex w-full justify-center">
                @if ($raffle->animation === RaffleAnimation::Wheel)
                    <x-screen.wheel :reel="$reel" :winner-index="$winnerIndex" :attempt="$projection->attempt" :landed="$landed" />
                @elseif ($raffle->animation === RaffleAnimation::Drum)
                    <x-screen.drum :reel="$reel" :winner-index="$winnerIndex" :attempt="$projection->attempt" :landed="$landed" />
                @elseif (! $landed)
                    <x-screen.reveal :name="$winner->name" :attempt="$projection->attempt" />
                @endif
            </div>

            @if ($landed)
                <x-screen.winner-card wire:key="winner-{{ $projection->attempt }}" :winner="$winner" :raffle="$raffle" :position="$projection->winner_position" :shows-membership="$settings->screen_shows_membership" />
                <x-screen.confetti wire:key="confetti-{{ $projection->attempt }}" />
            @endif

            @if ($earlier->isNotEmpty())
                <div class="flex max-w-5xl flex-wrap items-center justify-center gap-2 px-6 text-sm text-white/50">
                    <span class="me-1 text-xs tracking-[0.3em] uppercase">{{ __('Already won') }}</span>
                    @foreach ($earlier as $previous)
                        <span wire:key="earlier-{{ $previous->id }}" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 py-1 ps-1 pe-3 text-white/75">
                            <span class="flex size-5 items-center justify-center rounded-full bg-gold-400/25 text-[0.65rem] font-semibold text-gold-200">{{ $previous->pivot->winner_position }}</span>
                            {{ $previous->short_name }}
                        </span>
                    @endforeach
                </div>
            @endif

            @if ($finale)
                {{-- After the last winner's moment, the whole roll of honor. --}}
                <div
                    wire:key="finale-{{ $projection->attempt }}"
                    x-data="{ shown: false }"
                    x-init="setTimeout(() => (shown = true), 6000)"
                    x-show="shown"
                    x-transition.opacity.duration.700ms
                    style="display: none"
                    class="absolute inset-0 z-20 flex items-center justify-center bg-stage-950/92 backdrop-blur-md"
                >
                    <x-screen.honor-roll :winners="$earlier->concat([$winner])" :raffle="$raffle" />
                </div>
            @endif
        @endif
    </main>
</div>
