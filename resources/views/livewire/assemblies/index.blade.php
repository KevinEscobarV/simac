<div>
    <x-page-header :title="__('Assemblies')" :description="__('Open the assembly, take attendance and follow the quorum live.')" />

    @if ($current = $this->current)
        {{-- Assembly in progress. The "dark" class scopes Flux's dark variants to the card. --}}
        <section class="dark mb-6 overflow-hidden rounded-2xl border border-brand-800 bg-institutional shadow-raised">
            <div class="space-y-5 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-300 opacity-60"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-brand-300"></span>
                            </span>
                            <span class="text-[0.66rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('Assembly in progress') }}</span>
                        </div>

                        <h2 class="mt-1.5 font-display text-2xl font-semibold text-white">{{ $current->name }}</h2>
                        <p class="mt-1 text-sm text-white/55">
                            {{ Str::ucfirst($current->date->translatedFormat('l j \d\e F \d\e Y')) }}
                            @if ($current->location)
                                · {{ $current->location }}
                            @endif
                        </p>
                    </div>

                    <flux:button icon="lock-closed" wire:click="confirmClosing" class="shrink-0">{{ __('Close assembly') }}</flux:button>
                </div>

                <div class="grid gap-3 lg:grid-cols-[auto_1fr] lg:items-stretch">
                    <div class="grid grid-cols-3 gap-2.5">
                        @foreach ([
                            ['value' => $current->present_count, 'label' => __('Those present'), 'tone' => 'text-gold-300'],
                            ['value' => $current->attendances_count - $current->present_count, 'label' => __('Already left'), 'tone' => 'text-white/80'],
                            ['value' => $current->attendances_count.'/'.$this->rollCount, 'label' => __('Registered'), 'tone' => 'text-white/80'],
                        ] as $counter)
                            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5">
                                <div class="font-display text-2xl leading-none font-semibold tabular-nums {{ $counter['tone'] }}">{{ $counter['value'] }}</div>
                                <div class="mt-1 text-[0.68rem] text-white/50">{{ $counter['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <x-assemblies.quorum :quorum="$this->quorum" adjustable />
                </div>
            </div>
        </section>

        {{-- Taking attendance --}}
        <div class="mb-3 flex flex-wrap items-start gap-2.5">
            <div class="min-w-64 flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    wire:keydown.enter="checkInFromSearch"
                    icon="magnifying-glass"
                    :placeholder="__('ID number, code or name…')"
                    :aria-label="__('Search the roll')"
                    clearable
                    autofocus
                />
                <flux:error name="search" />
                <flux:error name="teacher" />
                <flux:error name="assembly" />
            </div>

            {{-- Four segments do not fit a phone: there it is a select. --}}
            <flux:radio.group wire:model.live="filter" variant="segmented" :aria-label="__('Show')" class="max-sm:hidden">
                <flux:radio value="todos" :label="__('Everyone')" />
                <flux:radio value="presentes" :label="__('Those present')" />
                <flux:radio value="salieron" :label="__('Already left')" />
                <flux:radio value="sin-registrar" :label="__('Not registered')" />
            </flux:radio.group>

            <div class="w-full sm:hidden">
                <flux:select wire:model.live="filter" :aria-label="__('Show')">
                    <flux:select.option value="todos">{{ __('Everyone') }}</flux:select.option>
                    <flux:select.option value="presentes">{{ __('Those present') }}</flux:select.option>
                    <flux:select.option value="salieron">{{ __('Already left') }}</flux:select.option>
                    <flux:select.option value="sin-registrar">{{ __('Not registered') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        <p class="mb-4 flex items-start gap-2 px-1 text-xs text-zinc-500 dark:text-zinc-400">
            <flux:icon.sparkles variant="micro" class="mt-px shrink-0 text-gold-500" />
            <span>
                {{ __('Type the ID number, the code or the name and press') }}
                <kbd class="rounded bg-zinc-200 px-1.5 font-sans text-zinc-700 dark:bg-white/10 dark:text-zinc-200">Enter</kbd>
                {{ __('to check in without the mouse. It works with a barcode reader too.') }}
            </span>
        </p>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200/80 px-5 py-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Roll of the assembly') }}</h2>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice('{0} No teachers|{1} :count teacher|[2,*] :count teachers', $teachers->total()) }}
                </span>
            </div>

            @if ($teachers->isEmpty())
                <x-empty-state
                    icon="magnifying-glass"
                    :title="__('No matches')"
                    :message="__('No teacher matches the search or the selected filter.')"
                />
            @else
                <ul wire:loading.delay.class="opacity-60" class="divide-y divide-zinc-100 transition-opacity dark:divide-white/5">
                    @foreach ($teachers as $teacher)
                        @php($attendance = $teacher->attendances->first())

                        <li wire:key="roll-{{ $teacher->id }}" class="flex items-center gap-3.5 px-5 py-3 transition-colors hover:bg-zinc-50 dark:hover:bg-white/3">
                            <flux:avatar size="sm" :name="$teacher->name" class="max-sm:hidden" />

                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $teacher->name }}</div>
                                <div class="flex flex-wrap items-center gap-x-2.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="tabular-nums">{{ __('ID :number', ['number' => $teacher->document_number]) }}</span>
                                    <span class="font-semibold text-zinc-600 dark:text-zinc-300">{{ $teacher->code }}</span>
                                    <span class="truncate max-sm:hidden">{{ $teacher->school->name }}</span>
                                </div>
                                <x-assemblies.attendance-status :attendance="$attendance" class="mt-1.5 md:hidden" />
                            </div>

                            @if ($attendance)
                                <div class="text-end text-xs text-zinc-500 tabular-nums max-sm:hidden dark:text-zinc-400">
                                    <div>{{ __('In :time', ['time' => $attendance->checked_in_at->translatedFormat('g:i a')]) }}</div>
                                    @if ($attendance->checked_out_at)
                                        <div class="text-zinc-400 dark:text-zinc-500">{{ __('Out :time', ['time' => $attendance->checked_out_at->translatedFormat('g:i a')]) }}</div>
                                    @endif
                                </div>
                            @endif

                            <x-assemblies.attendance-status :attendance="$attendance" class="w-28 justify-center max-md:hidden" />

                            <div class="flex shrink-0 items-center gap-1">
                                @if ($attendance?->isPresent())
                                    <flux:button size="sm" icon="arrow-right-start-on-rectangle" wire:click="checkOut({{ $teacher->id }})">
                                        {{ __('Check out') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        size="sm"
                                        :variant="$attendance ? 'filled' : 'primary'"
                                        icon="check"
                                        wire:click="checkIn({{ $teacher->id }})"
                                    >
                                        {{ $attendance ? __('Back in') : __('Check in') }}
                                    </flux:button>
                                @endif

                                @if ($attendance)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="confirmVoiding({{ $teacher->id }})"
                                        :aria-label="__('Void the record of :name', ['name' => $teacher->name])"
                                    />
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($teachers->hasPages())
                    <div class="border-t border-zinc-200/80 px-5 py-3 dark:border-white/10">
                        <flux:pagination :paginator="$teachers" />
                    </div>
                @endif
            @endif
        </div>
    @else
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <x-empty-state
                icon="calendar-days"
                :title="__('No assembly is open')"
                :message="__('Attendance and the raffle among those present need an open assembly. Open one to start taking check-ins.')"
            >
                <x-slot:action>
                    <flux:button variant="primary" icon="plus" wire:click="startOpening">{{ __('Open assembly') }}</flux:button>
                </x-slot:action>
            </x-empty-state>
        </div>
    @endif

    {{-- Past assemblies --}}
    @if ($this->pastAssemblies->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <header class="flex items-center gap-2 border-b border-zinc-200/80 px-5 py-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Past assemblies') }}</h2>
                <flux:badge size="sm">{{ $this->pastAssemblies->count() }}</flux:badge>
            </header>

            @unless ($current)
                <flux:error name="assembly" class="px-5 pt-3" />
            @endunless

            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($this->pastAssemblies as $past)
                    <li wire:key="past-{{ $past->id }}" class="flex items-center gap-4 px-5 py-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 max-sm:hidden dark:bg-white/5 dark:text-zinc-400">
                            <flux:icon.calendar variant="mini" class="size-4.5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $past->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ Str::ucfirst($past->date->translatedFormat('j \d\e F \d\e Y')) }}
                                · {{ trans_choice('{0} nobody registered|{1} :count registered|[2,*] :count registered', $past->attendances_count) }}
                            </div>
                        </div>

                        <flux:button size="sm" icon="arrow-path" wire:click="reopen({{ $past->id }})">{{ __('Reopen') }}</flux:button>

                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="confirmDeletion({{ $past->id }})"
                            :aria-label="__('Delete :name', ['name' => $past->name])"
                        />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Open an assembly --}}
    <flux:modal name="assembly-open" class="w-full md:w-lg">
        <form wire:submit="open" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Open assembly') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Check-ins and check-outs will be registered on it.') }}</flux:text>
            </div>

            <flux:input wire:model="form.name" :label="__('Name')" :placeholder="__('e.g. Ordinary General Assembly')" required autocomplete="off" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.date" type="date" :label="__('Date')" required />
                <flux:input wire:model="form.location" :label="__('Place')" :badge="__('Optional')" :placeholder="__('e.g. Union office · Yopal')" autocomplete="off" />
            </div>

            <x-assemblies.quorum-fields model="form" :form="$form" :union-members="$this->unionMembers" />

            <p class="flex items-start gap-2 rounded-xl bg-zinc-100 px-3.5 py-3 text-xs text-zinc-600 dark:bg-white/5 dark:text-zinc-400">
                <flux:icon.information-circle variant="micro" class="mt-px size-3.5 shrink-0 text-gold-500" />
                {{ __('Only one assembly can be open at a time: it is the one attendance is taken on and the one the raffle among those present uses.') }}
            </p>

            <flux:error name="assembly" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Open assembly') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Adjust the quorum --}}
    <flux:modal name="assembly-quorum" class="w-full md:w-md">
        <form wire:submit="saveQuorum" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Quorum of the assembly') }}</flux:heading>
                <flux:text class="mt-1">{{ __('It is measured over the union members in the room, and it updates with every check-in and check-out.') }}</flux:text>
            </div>

            <x-assemblies.quorum-fields model="quorumForm" :form="$quorumForm" :union-members="$this->unionMembers" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Close --}}
    <flux:modal name="assembly-close" class="w-full md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Close ":name"?', ['name' => $this->current?->name]) }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('It will stop taking check-ins and check-outs, and whoever is still in stays as present in its history. You can reopen it if needed.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" wire:click="close">{{ __('Close assembly') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Void a record --}}
    <flux:modal name="attendance-void" class="w-full md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Void the record of :name?', ['name' => $voiding?->name]) }}</flux:heading>
                <flux:text class="mt-1">{{ __('Their check-in and check-out at this assembly are deleted, as if they had never been registered. Use it to fix a mistake.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="voidAttendance">{{ __('Void record') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete an assembly --}}
    <flux:modal name="assembly-delete" class="w-full md:w-md">
        <div class="space-y-6">
            @if ($deletionBlocker)
                <div>
                    <flux:heading size="lg" class="font-display">{{ __(':name cannot be deleted', ['name' => $deleting?->name]) }}</flux:heading>
                    <flux:text class="mt-1">{{ $deletionBlocker }}</flux:text>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="primary">{{ __('Understood') }}</flux:button>
                    </flux:modal.close>
                </div>
            @else
                <div>
                    <flux:heading size="lg" class="font-display">{{ __('Delete :name?', ['name' => $deleting?->name]) }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Nobody was registered at it, so nothing else is affected.') }}</flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
