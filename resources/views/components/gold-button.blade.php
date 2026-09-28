@props([
    'icon' => null,
    'href' => null,
])

{{-- The ceremonial action of the page: load a raffle, give the "Go!", print the cards. A link when it has an href. --}}
@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @else type="button" @endif {{ $attributes->class('inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-gold-400 px-5 py-3.5 text-base font-semibold text-brand-950 shadow-gold transition hover:bg-gold-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-300 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none') }}>
    @if ($icon)
        <flux:icon :icon="$icon" variant="mini" class="size-5" />
    @endif

    {{ $slot }}
</{{ $tag }}>
