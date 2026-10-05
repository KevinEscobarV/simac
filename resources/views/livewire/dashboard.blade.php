@use('App\Models\Assembly')
@use('App\Models\Projection')
@use('App\Models\Raffle')
@use('App\Models\Teacher')

@php
    $user = auth()->user();
    $assembly = $this->assembly;

    $shortcuts = collect([
        ['allowed' => $user->can('create', Raffle::class), 'href' => route('raffles.create'), 'icon' => 'gift', 'label' => __('New raffle'), 'hint' => __('Set it up and drive the screens')],
        ['allowed' => $user->can('useDesk', Assembly::class), 'href' => route('desk'), 'icon' => 'clipboard-document-check', 'label' => __('Registration desk'), 'hint' => __('Check-ins and check-outs at the door')],
        ['allowed' => $user->can('viewAny', Teacher::class), 'href' => route('teachers.index'), 'icon' => 'academic-cap', 'label' => __('Teachers'), 'hint' => __('The union roll')],
        ['allowed' => $user->can('viewAny', Teacher::class), 'href' => route('teachers.cards'), 'icon' => 'identification', 'label' => __('Cards'), 'hint' => __('With the barcode the desk reads')],
        ['allowed' => $user->can('viewAny', Raffle::class), 'href' => route('raffles.index'), 'icon' => 'document-text', 'label' => __('History'), 'hint' => __('Every record, in PDF')],
        ['allowed' => $user->can('watch', Projection::class), 'href' => route('screen'), 'icon' => 'tv', 'label' => __('Projection screen'), 'hint' => __('Opens in a tab of its own'), 'blank' => true],
    ])->where('allowed');
@endphp

<div class="space-y-6">
    <x-page-header
        :title="__('Home')"
        :description="__('Hello, :name.', ['name' => $user->name]).' '.Str::ucfirst(now()->translatedFormat('l j \d\e F'))"
    />

    @if ($this->settings->hasEvent())
        <x-event.banner
            :title="$this->settings->event_title"
            :subtitle="$this->settings->event_subtitle"
            :image="$this->settings->eventImageUrl()"
        />
    @endif

    {{-- A raffle on screen comes first: someone may be waiting for the next order. --}}
    @if ($this->projection->isLive() && $user->can('control', Projection::class))
        <a
            href="{{ route('raffles.create') }}"
            wire:navigate
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-gold-400/50 bg-gold-50 px-5 py-4 text-gold-900 transition hover:border-gold-400 hover:bg-gold-100 dark:bg-gold-400/10 dark:text-gold-100 dark:hover:bg-gold-400/15"
        >
            <span class="relative flex size-2.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-400 opacity-60"></span>
                <span class="relative inline-flex size-2.5 rounded-full bg-gold-500"></span>
            </span>
            <span class="min-w-48 flex-1 text-sm">
                <span class="font-semibold">{{ __('Raffle on screen') }}:</span>
                {{ $this->projection->raffle?->prize }}
            </span>
            <span class="inline-flex items-center gap-1 text-sm font-semibold">
                {{ __('Go to the console') }}
                <flux:icon.arrow-right variant="micro" class="size-4" />
            </span>
        </a>
    @endif

    <div class="grid items-start gap-5 lg:grid-cols-3">
        {{-- The assembly in progress --}}
        @can('followLive', Assembly::class)
            <section class="dark relative overflow-hidden rounded-2xl border border-brand-800 bg-institutional text-white shadow-raised lg:col-span-2">
                <div class="pointer-events-none absolute -top-24 right-6 size-64 rounded-full bg-gold-500/14 blur-3xl"></div>

                <div class="relative space-y-5 p-6 sm:p-7">
                    @if ($assembly)
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">
                                    <span class="size-1.5 rounded-full bg-brand-300"></span>
                                    {{ __('Assembly in progress') }}
                                </div>
                                <h2 class="mt-1.5 font-display text-2xl leading-tight font-semibold text-balance sm:text-3xl">{{ $assembly->name }}</h2>
                                <p class="mt-1 text-sm text-white/55">
                                    {{ Str::ucfirst($assembly->date->translatedFormat('l j \d\e F')) }}
                                    @if ($assembly->location)
                                        · {{ $assembly->location }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5">
                            @foreach ([
                                ['value' => $assembly->present_count, 'label' => __('Those present'), 'tone' => 'text-gold-300'],
                                ['value' => $assembly->attendances_count - $assembly->present_count, 'label' => __('Already left'), 'tone' => 'text-white/85'],
                                ['value' => $assembly->attendances_count, 'label' => __('Registered'), 'tone' => 'text-white/85'],
                            ] as $counter)
                                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-center">
                                    <div class="font-display text-2xl leading-none font-semibold tabular-nums sm:text-3xl {{ $counter['tone'] }}">{{ $counter['value'] }}</div>
                                    <div class="mt-1.5 text-[0.68rem] text-white/50">{{ $counter['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <x-assemblies.quorum :quorum="$this->quorum" />

                        <div class="flex flex-wrap gap-2.5 border-t border-white/10 pt-5">
                            @can('useDesk', Assembly::class)
                                <x-gold-button icon="clipboard-document-check" :href="route('desk')" wire:navigate class="py-2.5 text-sm max-sm:w-full">
                                    {{ __('Registration desk') }}
                                </x-gold-button>
                            @endcan
                            @can('viewAny', Assembly::class)
                                <flux:button icon="calendar-days" :href="route('assemblies.index')" wire:navigate class="max-sm:w-full">{{ __('See the assembly') }}</flux:button>
                            @endcan
                        </div>
                    @else
                        <div>
                            <div class="text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('No assembly open') }}</div>
                            <h2 class="mt-1.5 font-display text-2xl leading-tight font-semibold text-balance sm:text-3xl">
                                {{ __('Roll, attendance and raffles') }}
                                <span class="block text-gold-300">{{ __('with verifiable records.') }}</span>
                            </h2>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/60">
                                {{ __('Open the assembly to take attendance at the desk and draw among those present.') }}
                            </p>
                        </div>

                        @can('create', Assembly::class)
                            <x-gold-button icon="calendar-days" :href="route('assemblies.index')" wire:navigate class="py-2.5 text-sm">
                                {{ __('Open assembly') }}
                            </x-gold-button>
                        @endcan
                    @endif
                </div>
            </section>
        @endcan

        {{-- Shortcuts --}}
        @if ($shortcuts->isNotEmpty())
            <nav aria-labelledby="shortcuts" class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                <h2 id="shortcuts" class="border-b border-zinc-200/80 px-5 py-3.5 text-sm font-semibold text-zinc-800 dark:border-white/10 dark:text-zinc-100">{{ __('Shortcuts') }}</h2>

                <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                    @foreach ($shortcuts as $shortcut)
                        <li>
                            <a
                                href="{{ $shortcut['href'] }}"
                                @if ($shortcut['blank'] ?? false) target="_blank" @else wire:navigate @endif
                                class="group flex items-center gap-3.5 px-5 py-3 transition hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-none dark:hover:bg-white/3 dark:focus-visible:bg-white/3"
                            >
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                    <flux:icon :icon="$shortcut['icon']" variant="mini" class="size-4.5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $shortcut['label'] }}</span>
                                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $shortcut['hint'] }}</span>
                                </span>
                                <flux:icon :icon="($shortcut['blank'] ?? false) ? 'arrow-up-right' : 'chevron-right'" variant="micro" class="size-4 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-zinc-500 dark:text-zinc-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>

    {{-- Numbers --}}
    @php
        $stats = $this->stats;
        $metrics = collect([
            $user->can('viewAny', Teacher::class) ? ['icon' => 'users', 'tone' => 'brand', 'label' => __('Teachers on the roll'), 'value' => $stats['teachers'], 'hint' => null] : null,
            $user->can('viewAny', Teacher::class) ? ['icon' => 'shield-check', 'tone' => 'brand', 'label' => __('Union members'), 'value' => $stats['members'], 'hint' => $stats['teachers'] > 0 ? __(':percent% of the roll', ['percent' => round($stats['members'] * 100 / $stats['teachers'])]) : null] : null,
            $user->can('viewAny', Assembly::class) ? ['icon' => 'calendar-days', 'tone' => 'zinc', 'label' => __('Assemblies'), 'value' => $stats['assemblies'], 'hint' => null] : null,
            $user->can('viewAny', Raffle::class) ? ['icon' => 'document-text', 'tone' => 'gold', 'label' => __('Raffle records'), 'value' => $stats['raffles'], 'hint' => null] : null,
        ])->filter();
    @endphp

    @if ($metrics->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($metrics as $metric)
                <x-metric :icon="$metric['icon']" :tone="$metric['tone']" :label="$metric['label']" :value="$metric['value']" :hint="$metric['hint']" />
            @endforeach
        </div>
    @endif

    {{-- The latest record --}}
    @can('viewAny', Raffle::class)
        @if ($this->latestRaffle)
            <div class="space-y-2.5">
                <x-raffles.featured-record :raffle="$this->latestRaffle" :projection="$this->projection" wire:key="latest-{{ $this->latestRaffle->id }}" />
                <div class="text-right">
                    <a href="{{ route('raffles.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-800 dark:text-brand-300 dark:hover:text-brand-200">
                        {{ __('See the whole history') }}
                        <flux:icon.arrow-right variant="micro" class="size-4" />
                    </a>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                <x-empty-state
                    icon="document-text"
                    :title="__('No records yet')"
                    :message="__('When you draw the first raffle, its winner, the filter applied and the list of participants will be recorded here.')"
                >
                    @can('create', Raffle::class)
                        <x-slot:action>
                            <flux:button size="sm" variant="primary" icon="gift" :href="route('raffles.create')" wire:navigate>{{ __('New raffle') }}</flux:button>
                        </x-slot:action>
                    @endcan
                </x-empty-state>
            </div>
        @endif
    @endcan

    {{-- Someone with no post yet --}}
    @if ($shortcuts->isEmpty() && $metrics->isEmpty())
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <x-empty-state
                icon="lock-closed"
                :title="__('Nothing to see here yet')"
                :message="__('Your user has no post assigned. Ask an administrator to give you a role.')"
            />
        </div>
    @endif
</div>
