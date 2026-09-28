@props([
    'raffle',
    'projection',
    'compact' => false,
])

{{-- The PDF names every winner, so it waits until the screens have shown them all. --}}
<span {{ $attributes->class('inline-flex') }}>
    @if ($raffle->isPublic($projection))
        @if ($compact)
            <flux:tooltip :content="__('Download PDF')">
                <flux:button
                    :href="route('raffles.pdf', $raffle)"
                    variant="ghost"
                    size="sm"
                    icon="arrow-down-tray"
                    :aria-label="__('Download record No. :number in PDF', ['number' => $raffle->id])"
                />
            </flux:tooltip>
        @else
            <flux:button :href="route('raffles.pdf', $raffle)" variant="primary" icon="arrow-down-tray">{{ __('Download PDF') }}</flux:button>
        @endif
    @else
        {{-- A disabled button gets no pointer events: the tooltip hangs from its wrapper. --}}
        <flux:tooltip :content="__('The record can be downloaded once the screen has shown every winner.')">
            <div>
                @if ($compact)
                    <flux:button variant="ghost" size="sm" icon="arrow-down-tray" disabled :aria-label="__('Download PDF')" />
                @else
                    <flux:button variant="primary" icon="arrow-down-tray" disabled>{{ __('Download PDF') }}</flux:button>
                @endif
            </div>
        </flux:tooltip>
    @endif
</span>
