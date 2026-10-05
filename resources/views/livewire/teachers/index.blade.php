<div>
    <x-page-header :title="__('Teachers')" :description="__('The union roll: who takes part in assemblies and raffles.')">
        <x-slot:actions>
            {{-- The cards of the same teachers the filters show. --}}
            <flux:button
                icon="identification"
                :href="route('teachers.cards', array_filter(['municipio' => $city, 'colegio' => $school, 'afiliacion' => $membership]))"
                wire:navigate
            >
                {{ __('Cards') }}
            </flux:button>
            <flux:button variant="primary" icon="user-plus" wire:click="create">
                {{ __('Register teacher') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Metrics --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-metric icon="users" tone="brand" :label="__('Teachers on the roll')" :value="$this->stats['teachers']" />
        <x-metric
            icon="shield-check"
            tone="brand"
            :label="__('Union members')"
            :value="$this->stats['members']"
            :hint="$this->stats['teachers'] > 0 ? __(':percent% of the roll', ['percent' => round($this->stats['members'] * 100 / $this->stats['teachers'])]) : null"
        />
        <x-metric icon="building-library" tone="gold" :label="__('Schools with teachers')" :value="$this->stats['schools']" />
        <x-metric icon="map-pin" :label="__('Municipalities with teachers')" :value="$this->stats['cities']" />
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-2.5">
        <div class="min-w-64 flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Name, ID number, code, school or municipality…')"
                :aria-label="__('Search teachers')"
                clearable
            />
        </div>

        <div class="w-full sm:w-48">
            <flux:select wire:model.live="city" :aria-label="__('Filter by municipality')">
                <flux:select.option value="">{{ __('All municipalities') }}</flux:select.option>
                @foreach ($this->cities as $cityOption)
                    <flux:select.option :value="$cityOption->id">{{ $cityOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-56">
            <flux:select wire:model.live="school" :aria-label="__('Filter by school')" :disabled="$city === ''">
                <flux:select.option value="">{{ $city === '' ? __('Pick a municipality first') : __('All schools') }}</flux:select.option>
                @foreach ($this->filterSchools as $schoolOption)
                    <flux:select.option :value="$schoolOption->id">{{ $schoolOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-44">
            <flux:select wire:model.live="membership" :aria-label="__('Filter by union membership')">
                <flux:select.option value="">{{ __('Members or not') }}</flux:select.option>
                <flux:select.option value="si">{{ __('Union members') }}</flux:select.option>
                <flux:select.option value="no">{{ __('Not members') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-3 dark:border-white/10">
            <flux:radio.group wire:model.live="status" variant="segmented" size="sm" :aria-label="__('Show')">
                <flux:radio value="activos" :label="__('Active (:count)', ['count' => $this->stats['teachers']])" />
                <flux:radio value="retirados" :label="__('Retired (:count)', ['count' => $this->stats['retired']])" />
            </flux:radio.group>

            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ trans_choice('{0} No teachers|{1} :count teacher|[2,*] :count teachers', $teachers->total()) }}
            </span>
        </div>

        @if ($teachers->isEmpty())
            @if ($this->hasFilters())
                <x-empty-state
                    icon="magnifying-glass"
                    :title="__('No matches')"
                    :message="__('No teacher meets the filters. Try loosening them.')"
                >
                    <x-slot:action>
                        <flux:button size="sm" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                    </x-slot:action>
                </x-empty-state>
            @elseif ($this->showingRetired())
                <x-empty-state
                    icon="archive-box"
                    :title="__('Nobody has been retired')"
                    :message="__('Teachers you retire from the roll will show up here, and you can bring them back.')"
                />
            @else
                <x-empty-state
                    icon="users"
                    :title="__('No teachers yet')"
                    :message="__('Register the first one to start taking attendance and running raffles.')"
                >
                    <x-slot:action>
                        <flux:button size="sm" variant="primary" icon="user-plus" wire:click="create">{{ __('Register teacher') }}</flux:button>
                    </x-slot:action>
                </x-empty-state>
            @endif
        @else
            <div class="px-5">
                <flux:table :paginate="$teachers" pagination:class="py-3" wire:loading.delay.class="opacity-60" class="transition-opacity">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Teacher') }}</flux:table.column>
                        <flux:table.column class="max-md:hidden">{{ __('Identification') }}</flux:table.column>
                        <flux:table.column class="max-lg:hidden">{{ __('Municipality') }}</flux:table.column>
                        <flux:table.column class="max-sm:hidden">{{ __('Membership') }}</flux:table.column>
                        <flux:table.column class="w-12"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($teachers as $teacher)
                            <flux:table.row :key="$teacher->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar size="sm" :name="$teacher->name" :class="$teacher->trashed() ? 'opacity-50' : ''" />

                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $teacher->name }}</div>
                                            <div class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                <flux:icon.building-library variant="micro" class="size-3 shrink-0" />
                                                <span class="truncate">{{ $teacher->school->name }}</span>
                                            </div>

                                            {{-- On narrow screens identification and membership live under the name. --}}
                                            <div class="mt-0.5 text-xs text-zinc-500 tabular-nums md:hidden dark:text-zinc-400">
                                                {{ $teacher->document_number }} · <span class="font-semibold">{{ $teacher->code }}</span>
                                            </div>
                                            <x-teachers.membership-badge :teacher="$teacher" class="mt-1.5 sm:hidden" />
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="max-md:hidden">
                                    <div class="text-sm text-zinc-700 tabular-nums dark:text-zinc-300">{{ $teacher->document_number }}</div>
                                    <div class="text-xs font-semibold tracking-wide text-zinc-400 tabular-nums dark:text-zinc-500">{{ $teacher->code }}</div>
                                </flux:table.cell>

                                <flux:table.cell class="max-lg:hidden">
                                    <flux:badge size="sm" icon="map-pin">{{ $teacher->school->city->name }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="max-sm:hidden">
                                    <x-teachers.membership-badge :teacher="$teacher" />
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="ellipsis-horizontal"
                                            inset="top bottom"
                                            :aria-label="__('Actions for :name', ['name' => $teacher->name])"
                                        />

                                        <flux:menu>
                                            @if ($teacher->trashed())
                                                <flux:menu.item icon="arrow-uturn-left" wire:click="reincorporate({{ $teacher->id }})">
                                                    {{ __('Bring back to the roll') }}
                                                </flux:menu.item>
                                            @else
                                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $teacher->id }})">
                                                    {{ __('Edit') }}
                                                </flux:menu.item>
                                                <flux:menu.item icon="identification" :href="route('teachers.cards.print', ['docente' => $teacher->id])" target="_blank">
                                                    {{ __('Print card') }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" icon="archive-box-arrow-down" wire:click="confirmRetirement({{ $teacher->id }})">
                                                    {{ __('Retire from the roll') }}
                                                </flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>

    <p class="mt-5 flex items-start gap-2 px-1 text-xs text-zinc-500 dark:text-zinc-400">
        <flux:icon.sparkles variant="micro" class="mt-px shrink-0 text-gold-500" />
        {{ __('Tap a teacher\'s membership to switch it on the spot. Their code identifies them at the desk, before the ID number or the name.') }}
    </p>

    {{-- Register / edit --}}
    <flux:modal name="teacher-form" class="w-full md:w-lg">
        <form wire:submit="save" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg" class="font-display">{{ $form->isEditing() ? __('Edit teacher') : __('Register teacher') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $form->isEditing()
                        ? __('If you change the code, print their card again: the barcode carries it.')
                        : __('The code is the number the union assigned: it is what the desk asks for.') }}
                </flux:text>
            </div>

            <flux:input wire:model="form.name" :label="__('Full name')" :placeholder="__('e.g. María Fernanda Rojas')" required autocomplete="off" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="form.code"
                    :label="__('Code')"
                    :placeholder="__('e.g. 0321')"
                    inputmode="numeric"
                    maxlength="10"
                    required
                    autocomplete="off"
                />

                <flux:input
                    wire:model="form.document_number"
                    :label="__('ID number')"
                    :placeholder="__('e.g. 1118541203')"
                    inputmode="numeric"
                    required
                    autocomplete="off"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="form.city_id" :label="__('Municipality')" :placeholder="__('Choose…')">
                    @foreach ($this->cities as $cityOption)
                        <flux:select.option :value="$cityOption->id">{{ $cityOption->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model="form.school_id"
                    :label="__('School')"
                    :placeholder="$form->city_id === '' ? __('Pick a municipality first') : __('Choose…')"
                    :disabled="$form->city_id === ''"
                >
                    @foreach ($this->formSchools as $schoolOption)
                        <flux:select.option :value="$schoolOption->id">{{ $schoolOption->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($form->city_id !== '')
                @if ($addingSchool)
                    <div class="-mt-2 space-y-3 rounded-xl border border-dashed border-zinc-300 p-3.5 dark:border-white/15">
                        <flux:input
                            wire:model="newSchool.name"
                            :label="__('New school in :city', ['city' => $this->cities->firstWhere('id', (int) $form->city_id)?->name])"
                            autocomplete="off"
                            x-init="$nextTick(() => $el.focus())"
                            x-on:keydown.enter.prevent="$wire.addSchool()"
                        />
                        <flux:error name="newSchool.city_id" />

                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="cancelAddingSchool">{{ __('Pick from the list') }}</flux:button>
                            <flux:button size="sm" variant="filled" icon="plus" wire:click="addSchool">{{ __('Add school') }}</flux:button>
                        </div>
                    </div>
                @else
                    <div class="-mt-3">
                        <flux:button size="sm" variant="subtle" icon="plus" wire:click="startAddingSchool">
                            {{ __('The school is not on the list') }}
                        </flux:button>
                    </div>
                @endif
            @endif

            <flux:checkbox
                wire:model="form.is_union_member"
                :label="__('Union member')"
                :description="__('Takes part in the raffles that are only for union members.')"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">
                    {{ $form->isEditing() ? __('Save changes') : __('Register teacher') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Retire --}}
    <flux:modal name="teacher-retire" class="w-full md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-display">{{ __('Retire :name from the roll?', ['name' => $retiring?->name]) }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('They will no longer appear in the roll, at the registration desk or in raffles. Their history is kept and you can bring them back at any time.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="retire">{{ __('Retire') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
