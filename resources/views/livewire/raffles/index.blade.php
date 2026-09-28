<div>
    <x-page-header :title="__('Raffle history')" :description="__('Every record is sealed before the animation: prize, winners, filters and the full list of participants.')">
        @can('create', App\Models\Raffle::class)
            <x-slot:actions>
                <flux:button variant="primary" icon="gift" :href="route('raffles.create')" wire:navigate>{{ __('New raffle') }}</flux:button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    @if ($this->assemblies->isNotEmpty() || $this->hasRafflesWithoutAssembly)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="w-full sm:w-80">
                <flux:select wire:model.live="assembly" :aria-label="__('Filter by assembly')">
                    <flux:select.option value="">{{ __('Every assembly') }}</flux:select.option>
                    @foreach ($this->assemblies as $assemblyOption)
                        <flux:select.option :value="$assemblyOption->id">
                            {{ $assemblyOption->name }} · {{ $assemblyOption->date->translatedFormat('j M Y') }} ({{ $assemblyOption->raffles_count }})
                        </flux:select.option>
                    @endforeach
                    @if ($this->hasRafflesWithoutAssembly)
                        <flux:select.option :value="App\Livewire\Raffles\Index::WITHOUT_ASSEMBLY">{{ __('With no assembly open') }}</flux:select.option>
                    @endif
                </flux:select>
            </div>

            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ trans_choice('{0} No records|{1} :count record|[2,*] :count records', $raffles->total()) }}
            </span>
        </div>
    @endif

    @if ($raffles->isEmpty())
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            @if ($assembly !== '')
                <x-empty-state icon="funnel" :title="__('No records here')" :message="__('No raffle was drawn with that filter.')">
                    <x-slot:action>
                        <flux:button size="sm" wire:click="$set('assembly', '')">{{ __('See every record') }}</flux:button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="document-text"
                    :title="__('No records yet')"
                    :message="__('When you draw the first raffle, its winner, the filter applied and the list of participants will be recorded here.')"
                >
                    @can('create', App\Models\Raffle::class)
                        <x-slot:action>
                            <flux:button size="sm" variant="primary" icon="gift" :href="route('raffles.create')" wire:navigate>{{ __('New raffle') }}</flux:button>
                        </x-slot:action>
                    @endcan
                </x-empty-state>
            @endif
        </div>
    @else
        @php
            // The latest record stands out on the first page; the rest follow as rows.
            $featured = $raffles->onFirstPage() ? $raffles->first() : null;
            $rows = $featured ? $raffles->getCollection()->skip(1) : $raffles->getCollection();
        @endphp

        <div class="space-y-5">
            @if ($featured)
                <x-raffles.featured-record :raffle="$featured" :projection="$this->projection" wire:key="featured-{{ $featured->id }}" />
            @endif

            @if ($rows->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200/80 px-5 py-3.5 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $featured ? __('Earlier records') : __('Records') }}</h2>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-white/5">
                        @foreach ($rows as $raffle)
                            <x-raffles.record-row :raffle="$raffle" :projection="$this->projection" wire:key="raffle-{{ $raffle->id }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($raffles->hasPages())
                <flux:pagination :paginator="$raffles" />
            @endif
        </div>
    @endif

    <p class="mt-5 flex items-start gap-2 px-1 text-xs text-zinc-500 dark:text-zinc-400">
        <flux:icon.shield-check variant="micro" class="mt-px shrink-0 text-brand-500" />
        {{ __('Each record also keeps the full list of participants, so the raffle can be audited before the members.') }}
    </p>
</div>
