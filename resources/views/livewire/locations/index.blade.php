<div>
    <x-page-header
        :title="__('Municipalities and schools')"
        :description="__('Every school of the roll belongs to a municipality. Pick one to see and add its schools.')"
    />

    <div class="grid items-start gap-5 lg:grid-cols-5">
        {{-- Municipalities --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card lg:col-span-2 dark:border-white/10 dark:bg-zinc-900">
            <header class="flex items-center gap-2 border-b border-zinc-200/80 py-3 ps-5 pe-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Municipalities') }}</h2>
                <flux:badge size="sm">{{ $this->cities->count() }}</flux:badge>

                <flux:spacer />

                <flux:tooltip :content="__('New municipality')">
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="createCity" :aria-label="__('New municipality')" />
                </flux:tooltip>
            </header>

            @if ($this->cities->isEmpty())
                <x-empty-state
                    icon="map-pin"
                    :title="__('No municipalities yet')"
                    :message="__('Add the first one to start registering its schools.')"
                />
            @else
                <ul class="max-h-80 divide-y divide-zinc-100 overflow-y-auto lg:max-h-[calc(100dvh-16rem)] dark:divide-white/5">
                    @foreach ($this->cities as $city)
                        {{-- While searching the list on the right is not about any one municipality. --}}
                        @php($selected = $search === '' && $city->is($this->selectedCity))

                        <li
                            wire:key="city-{{ $city->id }}"
                            @if ($selected) x-init="$el.scrollIntoView({ block: 'nearest' })" @endif
                            @class([
                                'relative flex items-center gap-1 pe-3 transition-colors',
                                'bg-brand-50/80 dark:bg-brand-500/10' => $selected,
                                'hover:bg-zinc-50 dark:hover:bg-white/3' => ! $selected,
                            ])
                        >
                            @if ($selected)
                                <span class="absolute inset-y-2 inset-s-0 w-1 rounded-e-full bg-brand-600 dark:bg-brand-400" aria-hidden="true"></span>
                            @endif

                            <button
                                type="button"
                                wire:click="selectCity({{ $city->id }})"
                                class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 py-2.5 ps-5 text-start outline-none focus-visible:underline"
                                @if ($selected) aria-current="true" @endif
                            >
                                <span @class([
                                    'flex size-9 shrink-0 items-center justify-center rounded-xl transition-colors',
                                    'bg-brand-700 text-white dark:bg-brand-500' => $selected,
                                    'bg-zinc-100 text-zinc-500 dark:bg-white/5 dark:text-zinc-400' => ! $selected,
                                ])>
                                    <flux:icon.map-pin variant="mini" class="size-4.5" />
                                </span>

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $city->name }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ trans_choice('{0} No schools|{1} :count school|[2,*] :count schools', $city->schools_count) }}
                                    </span>
                                </span>
                            </button>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    :aria-label="__('Actions for :name', ['name' => $city->name])"
                                />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="editCity({{ $city->id }})">
                                        {{ __('Rename') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmCityDeletion({{ $city->id }})">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Schools --}}
        <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card lg:col-span-3 dark:border-white/10 dark:bg-zinc-900">
            <header class="flex flex-wrap items-center gap-x-3 gap-y-2.5 border-b border-zinc-200/80 px-5 py-3 dark:border-white/10">
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <h2 class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                        @if ($search !== '')
                            {{ __('Search results') }}
                        @elseif ($this->selectedCity)
                            {{ __('Schools in :city', ['city' => $this->selectedCity->name]) }}
                        @else
                            {{ __('Schools') }}
                        @endif
                    </h2>
                    <flux:badge size="sm">{{ $this->schools->count() }}</flux:badge>
                </div>

                <div class="w-full sm:w-72">
                    <flux:input
                        size="sm"
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        :placeholder="__('Search in every municipality…')"
                        :aria-label="__('Search schools')"
                        clearable
                    />
                </div>
            </header>

            @if ($this->schools->isNotEmpty())
                <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                    @foreach ($this->schools as $school)
                        <li wire:key="school-{{ $school->id }}" class="flex items-center gap-3 py-2.5 ps-5 pe-3 transition-colors hover:bg-zinc-50 dark:hover:bg-white/3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gold-50 text-gold-700 dark:bg-gold-400/10 dark:text-gold-300">
                                <flux:icon.building-library variant="mini" class="size-4.5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $school->name }}</div>
                                @if ($search !== '')
                                    <div class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <flux:icon.map-pin variant="micro" class="size-3" />
                                        {{ $school->city->name }}
                                    </div>
                                @endif
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    :aria-label="__('Actions for :name', ['name' => $school->name])"
                                />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="editSchool({{ $school->id }})">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmSchoolDeletion({{ $school->id }})">
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </li>
                    @endforeach
                </ul>
            @elseif ($search !== '')
                <x-empty-state
                    icon="magnifying-glass"
                    :title="__('No schools found')"
                    :message="__('No school in any municipality matches “:search”.', ['search' => $search])"
                />
            @elseif ($this->selectedCity)
                <x-empty-state
                    icon="building-library"
                    :title="__(':city has no schools yet', ['city' => $this->selectedCity->name])"
                    :message="__('Add the first one with the field below.')"
                />
            @else
                <x-empty-state
                    icon="building-library"
                    :title="__('Add a municipality first')"
                    :message="__('Schools are registered inside a municipality.')"
                />
            @endif

            @if ($search === '' && $this->selectedCity)
                <form wire:submit="addSchool" class="border-t border-zinc-200/80 bg-zinc-50/70 px-5 py-3.5 dark:border-white/10 dark:bg-white/2" novalidate>
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <flux:input
                                wire:model="newSchool.name"
                                :placeholder="__('New school…')"
                                :aria-label="__('Name of the new school in :city', ['city' => $this->selectedCity->name])"
                                autocomplete="off"
                            />
                        </div>

                        <flux:button type="submit" variant="primary" icon="plus">{{ __('Add') }}</flux:button>
                    </div>

                    <flux:error name="newSchool.name" />
                    <flux:error name="newSchool.city_id" />
                </form>
            @endif
        </section>
    </div>

    <p class="mt-5 flex items-start gap-2 px-1 text-xs text-zinc-500 dark:text-zinc-400">
        <flux:icon.shield-check variant="micro" class="mt-px shrink-0 text-brand-600 dark:text-brand-400" />
        {{ __('A municipality that still has schools cannot be deleted: move or delete its schools first.') }}
    </p>

    {{-- Create / rename a municipality --}}
    <flux:modal name="city-form" class="w-full md:w-md">
        <form wire:submit="saveCity" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">
                    {{ $cityForm->isEditing() ? __('Rename municipality') : __('New municipality') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $cityForm->isEditing() ? __('Its schools and teachers stay where they are.') : __('You can add its schools right after.') }}
                </flux:text>
            </div>

            <flux:input wire:model="cityForm.name" :label="__('Name')" required autocomplete="off" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">
                    {{ $cityForm->isEditing() ? __('Save changes') : __('Create municipality') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit or move a school --}}
    <flux:modal name="school-form" class="w-full md:w-md">
        <form wire:submit="saveSchool" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Edit school') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Rename it, or move it to another municipality if it was registered in the wrong one.') }}</flux:text>
            </div>

            <flux:input wire:model="schoolForm.name" :label="__('Name')" required autocomplete="off" />

            <flux:select wire:model="schoolForm.city_id" :label="__('Municipality')">
                @foreach ($this->cities as $city)
                    <flux:select.option :value="$city->id">{{ $city->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete a municipality --}}
    <flux:modal name="city-delete" class="w-full md:w-md">
        <div class="space-y-6">
            @if ($cityDeletionBlocker)
                <div>
                    <flux:heading size="lg" class="font-display">{{ __(':name cannot be deleted yet', ['name' => $deletingCity?->name]) }}</flux:heading>
                    <flux:text class="mt-1">{{ $cityDeletionBlocker }}</flux:text>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="primary">{{ __('Understood') }}</flux:button>
                    </flux:modal.close>
                </div>
            @else
                <div>
                    <flux:heading size="lg" class="font-display">{{ __('Delete :name?', ['name' => $deletingCity?->name]) }}</flux:heading>
                    <flux:text class="mt-1">{{ __('It has no schools, so nothing else is affected.') }}</flux:text>
                    <flux:error name="city" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="deleteCity">{{ __('Delete') }}</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- Delete a school --}}
    <flux:modal name="school-delete" class="w-full md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Delete :name?', ['name' => $deletingSchool?->name]) }}</flux:heading>
                <flux:text class="mt-1">{{ __('It will be removed from the list of schools.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteSchool">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
