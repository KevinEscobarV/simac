@props([
    'compact' => false,
])

{{--
    The event on the sign-in pages: beside the form on wide screens, and above
    it, smaller, on a phone (what the desks use). $settings comes from a view
    composer (AppServiceProvider). Without an event it shows the slot, if any.
--}}
@if ($settings->hasEvent())
    @php
        $image = $settings->eventImageUrl();
    @endphp

    @if ($compact)
        <div {{ $attributes->class('flex items-center gap-4 rounded-2xl border border-brand-800 bg-institutional p-4 text-white') }}>
            @if ($image)
                <img src="{{ $image }}" alt="{{ filled($settings->event_title) ? '' : __('Event image') }}" class="h-20 w-auto max-w-[35%] shrink-0 object-contain">
            @endif

            <div class="min-w-0">
                <div class="text-[0.6rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('Event') }}</div>
                @if (filled($settings->event_title))
                    <div class="mt-1 font-display text-lg leading-tight font-semibold text-balance">{{ $settings->event_title }}</div>
                @endif
                @if (filled($settings->event_subtitle))
                    <div class="mt-0.5 text-xs text-white/60">{{ $settings->event_subtitle }}</div>
                @endif
            </div>
        </div>
    @else
        <div {{ $attributes->class('flex items-center gap-8') }}>
            @if ($image)
                <img src="{{ $image }}" alt="{{ filled($settings->event_title) ? '' : __('Event image') }}" class="max-h-[46vh] w-auto max-w-[42%] shrink-0 object-contain drop-shadow-[0_20px_40px_rgba(0,0,0,0.5)]">
            @endif

            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-semibold tracking-[0.24em] text-gold-300/80 uppercase">
                    <flux:icon.sparkles variant="micro" class="size-4" />
                    {{ __('Event') }}
                </div>
                @if (filled($settings->event_title))
                    <h2 class="mt-3 font-display text-4xl leading-[1.12] font-semibold text-balance xl:text-5xl">{{ $settings->event_title }}</h2>
                @endif
                @if (filled($settings->event_subtitle))
                    <p class="mt-4 text-lg text-white/65">{{ $settings->event_subtitle }}</p>
                @endif
            </div>
        </div>
    @endif
@else
    {{ $slot }}
@endif
