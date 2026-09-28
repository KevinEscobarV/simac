@use('App\Support\CardSelection')

@php
    $sheets = (int) ceil($count / CardSelection::PER_SHEET);
@endphp

<div>
    <x-page-header :title="__('Cards')" :description="__('Print the teachers\' cards: the desk reads their barcode in one go.')">
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('teachers.index')" wire:navigate>{{ __('Teachers') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap items-center gap-2.5">
        <div class="w-full sm:w-52">
            <flux:select wire:model.live="city" :aria-label="__('Filter by municipality')">
                <flux:select.option value="">{{ __('All municipalities') }}</flux:select.option>
                @foreach ($this->cities as $cityOption)
                    <flux:select.option :value="$cityOption->id">{{ $cityOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-full sm:w-60">
            <flux:select wire:model.live="school" :aria-label="__('Filter by school')" :disabled="$city === ''">
                <flux:select.option value="">{{ $city === '' ? __('Pick a municipality first') : __('All schools') }}</flux:select.option>
                @foreach ($this->schools as $schoolOption)
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

    {{-- What will be printed --}}
    <section class="dark mb-6 overflow-hidden rounded-2xl border border-brand-800 bg-institutional shadow-raised">
        <div class="flex flex-wrap items-center gap-5 p-6">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gold-400/15 text-gold-300">
                <flux:icon.identification class="size-6" />
            </span>

            <div class="min-w-48 flex-1">
                <div class="font-display text-2xl font-semibold text-white">
                    {{ trans_choice('{0} No cards|{1} :count card|[2,*] :count cards', $count) }}
                </div>
                <div class="mt-0.5 text-sm text-white/55">
                    {{ $count > 0 ? trans_choice('{1} :count letter sheet, 8 cards per sheet|[2,*] :count letter sheets, 8 cards per sheet', $sheets) : __('No active teacher meets the filters.') }}
                </div>
            </div>

            @if ($count > 0)
                <x-gold-button icon="printer" :href="$printUrl" target="_blank" class="max-sm:w-full">
                    {{ __('Print cards') }}
                </x-gold-button>
            @endif
        </div>

        <p class="flex items-start gap-2 border-t border-white/10 px-6 py-3.5 text-xs text-white/50">
            <flux:icon.light-bulb variant="micro" class="mt-px size-3.5 shrink-0 text-gold-300/70" />
            {{ __('In the print dialog choose letter size, 100% scale and "Background graphics". Then cut along the edge of each card.') }}
        </p>
    </section>

    @if ($count > 0)
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $sheets > 1 ? __('Preview · first sheet') : __('Preview') }}</h2>
            <span class="text-xs text-zinc-500 max-sm:hidden dark:text-zinc-400">{{ __('Actual size') }}</span>
        </div>

        {{-- Paper stays white in dark mode too: it is what comes out of the printer. On a phone the cards shrink to fit. --}}
        <div class="overflow-x-auto rounded-2xl border border-zinc-200/80 bg-zinc-100 p-4 sm:p-6 dark:border-white/10 dark:bg-zinc-900">
            <div class="mx-auto grid w-fit gap-4 bg-white p-4 shadow-card max-sm:zoom-[0.85] sm:grid-cols-2 sm:gap-x-[8mm] sm:gap-y-[6mm] sm:p-[8mm]">
                @foreach ($preview as $teacher)
                    <x-teachers.card :teacher="$teacher" wire:key="card-{{ $teacher->id }}" />
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <x-empty-state icon="identification" :title="__('No cards to print')" :message="__('No active teacher meets the filters. Try loosening them.')" />
        </div>
    @endif

    <p class="mt-5 flex items-start gap-2 px-1 text-xs text-zinc-500 dark:text-zinc-400">
        <flux:icon.qr-code variant="micro" class="mt-px shrink-0 text-brand-500" />
        {{ __('The barcode carries the teacher code. At the desk, the scanner types it and presses Enter: the check-in is registered on the spot.') }}
    </p>
</div>
