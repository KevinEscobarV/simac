@props([
    'projection',
    'winners',
    'peeking' => false,
    'screens' => 0,
])

{{--
    The console of the raffle on screen. Nothing is projected here: from here
    the screens are driven. The current winner stays hidden until the screen
    reveals it, unless "See before" is used.
--}}
@use('App\Enums\ProjectionPhase')

@php
    $raffle = $projection->raffle;
    $phase = $projection->phase;
    $position = $projection->winner_position;
    $total = $raffle->winners_count;
    $several = $total > 1;
    $current = $winners->first(fn ($winner) => $winner->pivot->winner_position === $position);
    $earlier = $winners->filter(fn ($winner) => $winner->pivot->winner_position < $position);
    $shown = $phase === ProjectionPhase::Winner || $peeking;
    $winnersLeft = $projection->hasWinnersLeft();

    $reached = match ($phase) {
        ProjectionPhase::Ready => 0,
        ProjectionPhase::Animating => 1,
        default => 2,
    };

    [$title, $detail] = match (true) {
        $phase === ProjectionPhase::Ready => [__('Ready to project'), __('The screen announces that the raffle is about to start. Give the order when the room is paying attention.')],
        $phase === ProjectionPhase::Animating => [__('Animation running'), __('The screen is running the animation. As soon as it ends, the result is in view.')],
        $winnersLeft => [__('Winner :position of :total on screen', ['position' => $position, 'total' => $total]), __('When the room is ready, launch the next one.')],
        $several => [__('Every winner on screen'), __('The screen shows the roll of honor until you release it.')],
        default => [__('Winner on screen'), __('The card stays projected until you release the screen.')],
    };
@endphp

<section {{ $attributes->class('dark overflow-hidden rounded-2xl border border-brand-800 bg-institutional shadow-raised') }}>
    <div class="space-y-6 p-6 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <ol class="flex flex-wrap items-center gap-2" aria-label="{{ __('Progress') }}">
                @foreach ([['label' => __('Loaded'), 'icon' => 'document-check'], ['label' => __('On screen'), 'icon' => 'tv'], ['label' => __('Winner'), 'icon' => 'trophy']] as $index => $step)
                    <li class="flex items-center gap-2">
                        <span @class([
                            'flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                            'border-gold-400/40 bg-gold-400/15 text-gold-200' => $index <= $reached,
                            'border-white/10 bg-white/5 text-white/35' => $index > $reached,
                        ])>
                            <flux:icon :icon="$step['icon']" variant="micro" class="size-3.5" />
                            {{-- On a phone only the current step keeps its name. --}}
                            <span @class(['max-sm:sr-only' => $index !== $reached])>{{ $step['label'] }}</span>
                        </span>

                        @if (! $loop->last)
                            <span @class(['h-px w-3 sm:w-5', 'bg-gold-400/40' => $index < $reached, 'bg-white/10' => $index >= $reached])></span>
                        @endif
                    </li>
                @endforeach
            </ol>

            <x-raffles.screens :count="$screens" />
        </div>

        <div>
            <h2 class="font-display text-2xl font-semibold text-white">{{ $title }}</h2>
            <p class="mt-1.5 text-sm text-white/55">{{ $detail }}</p>
        </div>

        <div class="flex flex-wrap gap-1.5 text-[0.7rem] font-medium">
            <span class="inline-flex items-center gap-1 rounded-md border border-gold-400/30 bg-gold-400/10 px-2 py-1 text-gold-200">
                <flux:icon.gift variant="micro" class="size-3.5" />
                {{ $raffle->prize }}
            </span>
            <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ $raffle->filter_description }}</span>
            <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ trans_choice('{1} :count participant|[2,*] :count participants', $raffle->participants_count) }}</span>
            @if ($several)
                <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ trans_choice('{1} :count winner|[2,*] :count winners', $total) }}</span>
            @endif
            <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ $raffle->animation->label() }}</span>
            @can('view', $raffle)
                <a href="{{ route('raffles.show', $raffle) }}" class="inline-flex items-center gap-1 rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70 transition hover:border-white/25 hover:text-white" wire:navigate>
                    {{ __('Record No. :number', ['number' => $raffle->id]) }}
                    <flux:icon.arrow-up-right variant="micro" class="size-3" />
                </a>
            @else
                <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/70">{{ __('Record No. :number', ['number' => $raffle->id]) }}</span>
            @endcan
        </div>

        @if ($earlier->isNotEmpty())
            <div>
                <div class="mb-2 text-[0.66rem] font-semibold tracking-[0.2em] text-white/45 uppercase">{{ __('Roll of honor') }}</div>
                <ol class="divide-y divide-white/8 overflow-hidden rounded-xl border border-white/10 bg-white/5">
                    @foreach ($earlier as $winner)
                        <li wire:key="winner-{{ $winner->id }}" class="flex items-center gap-3 px-4 py-2.5">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gold-400/20 font-display text-sm font-semibold text-gold-200">{{ $winner->pivot->winner_position }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-white">{{ $winner->name }}</div>
                                <div class="truncate text-xs text-white/50">{{ $winner->school->name }} · {{ $winner->school->city->name }}</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($current && $shown)
            <div class="flex items-center gap-4 rounded-2xl border border-gold-400/30 bg-gold-400/10 p-4">
                <flux:avatar size="lg" :name="$current->name" class="ring-2 ring-gold-300/60 max-sm:hidden" />
                <div class="min-w-0 flex-1">
                    <div class="text-[0.66rem] font-semibold tracking-[0.28em] text-gold-300/80 uppercase">
                        {{ $several ? __('Winner :position of :total', ['position' => $position, 'total' => $total]) : __('Winner') }}
                    </div>
                    <div class="font-display text-2xl leading-tight font-semibold text-balance text-white sm:truncate">{{ $current->name }}</div>
                    <div class="text-sm text-white/55 sm:truncate">{{ $current->school->name }} · {{ $current->school->city->name }}</div>
                    @if ($phase !== ProjectionPhase::Winner)
                        <div class="mt-1 flex items-center gap-1.5 text-xs text-gold-200/80">
                            <flux:icon.eye variant="micro" class="size-3.5" />
                            {{ __('Only you can see it: the screen has not shown it yet.') }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/55">
                <flux:icon.shield-check class="size-5 shrink-0 text-gold-300/70" />
                <span class="min-w-48 flex-1">
                    {{ $several ? __('Winner :position of :total is already sealed in the record. The screen reveals it.', ['position' => $position, 'total' => $total]) : __('The result is already sealed in the record. The screen reveals it.') }}
                </span>
                <flux:button size="sm" variant="ghost" icon="eye" wire:click="peek">{{ __('See before') }}</flux:button>
            </div>
        @endif

        {{-- An animation launched with no screen on would run for nobody: the server refuses it too. --}}
        @if ($screens === 0)
            <p class="flex items-start gap-2.5 rounded-xl border border-gold-400/30 bg-gold-400/10 px-4 py-3 text-sm text-gold-100">
                <flux:icon.tv variant="mini" class="mt-0.5 size-4 shrink-0 text-gold-300" />
                <span>
                    {{ __('No screen is connected.') }}
                    <a href="{{ route('screen') }}" target="_blank" class="font-semibold text-gold-200 underline underline-offset-4">{{ __('Open the projection screen') }}</a>
                    {{ __('(or reload it) to launch.') }}
                </span>
            </p>
        @endif

        <div class="flex flex-wrap gap-2.5 border-t border-white/10 pt-5">
            @if ($phase === ProjectionPhase::Ready)
                <x-gold-button icon="sparkles" wire:click="launch" wire:loading.attr="disabled" :disabled="$screens === 0" class="flex-1">
                    {{ __('Go! Launch the animation') }}
                </x-gold-button>
            @elseif ($phase === ProjectionPhase::Animating)
                <span class="flex flex-1 items-center justify-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-5 py-3.5 text-sm font-semibold text-white/65">
                    <span class="relative flex size-2.5">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-300 opacity-60"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-gold-300"></span>
                    </span>
                    {{ __('Projecting…') }}
                </span>
            @elseif ($winnersLeft)
                <x-gold-button icon="forward" wire:click="launch" wire:loading.attr="disabled" :disabled="$screens === 0" class="flex-1">
                    {{ __('Next winner (:position of :total)', ['position' => $position + 1, 'total' => $total]) }}
                </x-gold-button>
            @endif

            @if ($phase !== ProjectionPhase::Ready)
                <flux:button icon="arrow-path" wire:click="repeat" :disabled="$screens === 0" class="max-sm:flex-1">{{ __('Repeat animation') }}</flux:button>
            @endif

            @if ($phase === ProjectionPhase::Winner && ! $winnersLeft)
                <flux:button variant="primary" icon="check" wire:click="release" class="max-sm:flex-1">{{ __('Finish and release') }}</flux:button>
            @else
                <flux:modal.trigger name="projection-release">
                    <flux:button variant="ghost" icon="x-mark" class="max-sm:flex-1">{{ __('Cancel projection') }}</flux:button>
                </flux:modal.trigger>
            @endif
        </div>
    </div>
</section>
