@props([
    'raffle',
])

{{-- Only the lack of quorum stands out: it is what has to be audited later. Having it is the norm. --}}
@if ($raffle->quorum_met === false)
    <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ __('No quorum') }}</flux:badge>
@endif
