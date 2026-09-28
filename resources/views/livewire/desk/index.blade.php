<div class="flex min-h-screen flex-col">
    @php($current = $this->current)

    {{-- The "dark" class scopes Flux's dark variants to the header, in both appearances. --}}
    <header class="dark bg-institutional border-b border-white/10 text-white">
        <div class="mx-auto max-w-5xl px-4 py-4 sm:px-7 sm:py-5">
            {{-- On a phone the counters wrap below, so the search box stays in view without scrolling. --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-4">
                <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                    <x-brand.mark :size="40" />

                    <div class="min-w-0">
                        <div class="text-[0.64rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('Registration desk') }}</div>

                        @if ($current)
                            <div class="mt-0.5 truncate font-display text-base leading-tight font-semibold sm:text-lg">{{ $current->name }}</div>
                            <div class="truncate text-xs text-white/50 max-sm:hidden">
                                {{ Str::ucfirst($current->date->translatedFormat('l j \d\e F')) }}
                                @if ($current->location)
                                    · {{ $current->location }}
                                @endif
                            </div>
                        @else
                            <div class="mt-0.5 text-sm text-white/55">{{ __('No assembly open') }}</div>
                        @endif
                    </div>
                </div>

                <div class="order-last flex w-full items-stretch gap-2 md:order-0 md:w-auto md:gap-2.5">
                    @if ($current)
                        @foreach ([
                            ['value' => $current->present_count, 'label' => __('Those present'), 'tone' => 'text-gold-300'],
                            ['value' => $current->attendances_count - $current->present_count, 'label' => __('Already left'), 'tone' => 'text-white/80'],
                            ['value' => $current->attendances_count.'/'.$this->rollCount, 'label' => __('Registered'), 'tone' => 'text-white/80'],
                        ] as $counter)
                            <div class="flex-1 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-center md:flex-none md:px-4 md:py-2.5">
                                <div class="font-display text-xl leading-none font-semibold tabular-nums md:text-2xl {{ $counter['tone'] }}">{{ $counter['value'] }}</div>
                                <div class="mt-1 text-[0.64rem] text-white/50 md:text-[0.68rem]">{{ $counter['label'] }}</div>
                            </div>
                        @endforeach
                    @endif

                    {{-- A wall clock: at the door the time is working data. --}}
                    <div
                        wire:ignore
                        x-data="{ time: @js(now()->translatedFormat('g:i a')) }"
                        x-init="
                            const format = new Intl.DateTimeFormat(document.documentElement.lang, { hour: 'numeric', minute: '2-digit', hour12: true, timeZone: @js(config('app.timezone')) })
                            const tick = () => (time = format.format(new Date()))
                            tick()
                            setInterval(tick, 10000)
                        "
                        class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-center max-md:hidden"
                    >
                        <div x-text="time" class="font-display text-2xl leading-none font-semibold whitespace-nowrap tabular-nums"></div>
                        <div class="mt-1 text-[0.68rem] text-white/50">{{ __('Time') }}</div>
                    </div>
                </div>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                    <x-user-menu>
                        @can('viewAny', App\Models\Assembly::class)
                            <flux:menu.item :href="route('assemblies.index')" icon="squares-2x2" wire:navigate>
                                {{ __('Administration panel') }}
                            </flux:menu.item>
                        @endcan
                    </x-user-menu>
                </flux:dropdown>
            </div>

            {{-- At the door, the question asked the most: it has to read at a glance. --}}
            @if ($current && $this->quorum)
                <x-assemblies.quorum :quorum="$this->quorum" class="mt-4" />
            @endif
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 space-y-5 px-4 py-6 sm:px-7 sm:py-7">
        @if ($current)
            <div class="relative">
                <flux:icon.magnifying-glass class="pointer-events-none absolute inset-s-4 top-1/2 size-6 -translate-y-1/2 text-zinc-400 sm:inset-s-5" />

                {{-- The key goes to the server as typed, so a barcode reader never waits for the list. --}}
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    x-data="{ busy: false }"
                    x-init="$el.focus()"
                    x-on:desk-ready.window="$el.focus()"
                    x-on:keydown.enter.prevent="
                        if (busy || ! $el.value.trim()) return
                        busy = true
                        try { await ($event.shiftKey ? $wire.checkOutByKey($el.value) : $wire.checkInByKey($el.value)) } finally { busy = false }
                    "
                    x-on:keydown.escape.prevent="$wire.$set('search', '')"
                    autocomplete="off"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    enterkeyhint="go"
                    aria-label="{{ __('ID number, teacher code or name') }}"
                    placeholder="{{ __('ID number, code or name…') }}"
                    class="w-full rounded-2xl border border-zinc-200 bg-white py-4 ps-12 pe-5 text-lg font-semibold text-zinc-900 shadow-card placeholder:font-normal placeholder:text-zinc-400 focus:ring-2 focus:ring-accent focus:outline-hidden sm:py-5 sm:ps-15 sm:text-2xl dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-500"
                />

                <span wire:loading wire:target="search" class="absolute inset-e-5 top-1/2 -translate-y-1/2 text-xs text-zinc-400">{{ __('Searching…') }}</span>
            </div>

            {{-- Shortcuts only exist with a keyboard: on a phone they are in the way. --}}
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 px-1 text-xs text-zinc-500 max-sm:hidden dark:text-zinc-400">
                <flux:icon.sparkles variant="micro" class="shrink-0 text-gold-500" />
                <kbd class="rounded bg-zinc-200 px-1.5 font-sans font-semibold text-zinc-700 dark:bg-white/10 dark:text-zinc-200">Enter</kbd>
                {{ __('checks in') }} ·
                <kbd class="rounded bg-zinc-200 px-1.5 font-sans font-semibold text-zinc-700 dark:bg-white/10 dark:text-zinc-200">Shift + Enter</kbd>
                {{ __('checks out') }} ·
                <kbd class="rounded bg-zinc-200 px-1.5 font-sans font-semibold text-zinc-700 dark:bg-white/10 dark:text-zinc-200">Esc</kbd>
                {{ __('clears the field') }}
            </p>

            <div role="status" aria-live="polite">
                @if ($errors->has('search'))
                    <x-desk.notice tone="error" :title="__('It could not be registered')" :detail="$errors->first('search')" />
                @elseif ($confirmation)
                    <x-desk.notice
                        wire:key="confirmation-{{ $confirmation['id'] }}"
                        :tone="$confirmation['tone']"
                        :title="$confirmation['title']"
                        :name="$confirmation['name']"
                        :detail="$confirmation['detail']"
                        :undoable="$confirmation['movement'] !== null"
                        :dismiss="$confirmation['id']"
                    />
                @endif
            </div>

            @if (trim($search) !== '')
                @php($results = $this->results)

                @if ($results->isEmpty())
                    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                        <x-empty-state
                            icon="magnifying-glass"
                            :title="__('No matches')"
                            :message="__('Check the ID number or the code, or search by the full name. A teacher who is not on the roll has to be added by the administrator.')"
                        />
                    </div>
                @else
                    <ul class="space-y-3">
                        @foreach ($results->take(App\Livewire\Desk\Index::RESULTS) as $teacher)
                            @php($attendance = $teacher->attendances->first())

                            <li wire:key="result-{{ $teacher->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-card dark:border-white/10 dark:bg-zinc-900">
                                <flux:avatar :name="$teacher->name" class="max-sm:hidden" />

                                <div class="min-w-48 flex-1">
                                    <div class="font-display text-lg leading-tight font-semibold text-zinc-900 dark:text-white">{{ $teacher->name }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="tabular-nums">{{ __('ID :number', ['number' => $teacher->document_number]) }}</span>
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-semibold text-zinc-600 dark:bg-white/10 dark:text-zinc-300">{{ $teacher->code }}</span>
                                        <span>{{ $teacher->school->name }} · {{ $teacher->school->city->name }}</span>
                                    </div>
                                    @if ($attendance)
                                        <div class="mt-1 text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                            {{ __('In :time', ['time' => $attendance->checked_in_at->translatedFormat('g:i a')]) }}
                                            @if ($attendance->checked_out_at)
                                                · {{ __('Out :time', ['time' => $attendance->checked_out_at->translatedFormat('g:i a')]) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <x-assemblies.attendance-status :attendance="$attendance" class="w-28 justify-center max-sm:hidden" />

                                @if ($attendance?->isPresent())
                                    <flux:button icon="arrow-right-start-on-rectangle" wire:click="checkOut({{ $teacher->id }})" class="min-w-36 max-sm:flex-1">
                                        {{ __('Check out') }}
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" icon="check" wire:click="checkIn({{ $teacher->id }})" class="min-w-36 max-sm:flex-1">
                                        {{ $attendance ? __('Re-entry') : __('Check in') }}
                                    </flux:button>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($results->count() > App\Livewire\Desk\Index::RESULTS)
                        <p class="px-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('There are more matches: type more of the name, or the full ID number.') }}
                        </p>
                    @endif
                @endif
            @else
                <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                    @if ($this->recentMovements->isEmpty())
                        <x-empty-state
                            icon="clock"
                            :title="__('No check-ins yet')"
                            :message="__('Ask for the ID number, the teacher code or the name, type it above and press Enter.')"
                        />
                    @else
                        <div class="flex items-center justify-between border-b border-zinc-200/80 px-5 py-3 dark:border-white/10">
                            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Latest movements') }}</h2>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('From every desk') }}</span>
                        </div>

                        <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($this->recentMovements as $movement)
                                <li wire:key="movement-{{ $movement->id }}" class="flex items-center gap-3 px-4 py-3 sm:gap-3.5 sm:px-5">
                                    <span class="w-18 shrink-0 text-xs font-semibold whitespace-nowrap text-zinc-500 tabular-nums sm:w-20 sm:text-sm dark:text-zinc-400">
                                        {{ ($movement->checked_out_at ?? $movement->updated_at)?->translatedFormat('g:i a') }}
                                    </span>

                                    <flux:avatar size="sm" :name="$movement->teacher->name" class="max-sm:hidden" />

                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $movement->teacher->name }}</div>
                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $movement->teacher->school->name }}</div>
                                    </div>

                                    @if ($movement->isPresent())
                                        <flux:badge size="sm" color="emerald" icon="check">
                                            <span class="max-sm:hidden">{{ __('Check-in') }}</span>
                                        </flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc" icon="arrow-right-start-on-rectangle">
                                            <span class="max-sm:hidden">{{ __('Check-out') }}</span>
                                        </flux:badge>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        @else
            {{-- Live updates announce the opening; the poll covers a desk left without them. --}}
            <div wire:poll.30s class="mx-auto max-w-xl rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                <x-empty-state
                    icon="clock"
                    :title="__('The assembly is not open yet')"
                    :message="__('The administrator enables check-ins when opening the assembly. This screen updates on its own as soon as that happens.')"
                >
                    <x-slot:action>
                        <span class="inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-500 dark:bg-white/5 dark:text-zinc-400">
                            <span class="size-1.5 animate-breathe rounded-full bg-gold-500"></span>
                            {{ __('Waiting for the opening') }}
                        </span>
                    </x-slot:action>
                </x-empty-state>
            </div>
        @endif
    </main>

    <footer class="border-t border-zinc-200 bg-white/60 px-4 py-3.5 text-center text-xs text-zinc-500 dark:border-white/10 dark:bg-zinc-900/60 dark:text-zinc-400">
        {{ __('SIMAC · This screen only registers check-ins and check-outs.') }}
        <span class="max-sm:hidden">{{ __('Opening or closing the assembly, changing the roll and holding the raffles is done from the administration panel.') }}</span>
    </footer>
</div>
