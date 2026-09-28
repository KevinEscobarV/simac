@props([
    'animation',
    'size' => 56,
])

{{-- A thumbnail of each animation, to choose by sight rather than by name. --}}
@php
    $slices = ['#2f8963', '#f2b72f', '#e0685f', '#4a9be0', '#9b6be0', '#e8913c'];
    $point = fn (float $angle, float $radius): string => round(32 + $radius * cos(deg2rad($angle)), 2).' '.round(34 + $radius * sin(deg2rad($angle)), 2);
@endphp

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" fill="none" aria-hidden="true" {{ $attributes->class('shrink-0') }}>
    <rect width="64" height="64" rx="16" fill="#08201a" />

    @switch($animation)
        @case(App\Enums\RaffleAnimation::Wheel)
            @foreach ($slices as $index => $color)
                <path d="M32 34 L{{ $point($index * 60, 20) }} A20 20 0 0 1 {{ $point(($index + 1) * 60, 20) }} Z" fill="{{ $color }}" opacity="0.92" />
            @endforeach
            <circle cx="32" cy="34" r="6" fill="#08201a" stroke="#f2b72f" stroke-width="2" />
            <polygon points="32,17 27,8 37,8" fill="#f2b72f" />
            @break

        @case(App\Enums\RaffleAnimation::Drum)
            <rect x="14" y="14" width="36" height="5" rx="2.5" fill="#ffffff" opacity="0.14" />
            <rect x="12" y="22" width="40" height="5" rx="2.5" fill="#ffffff" opacity="0.28" />
            <rect x="8" y="30" width="48" height="10" rx="5" fill="#f2b72f" />
            <rect x="15" y="33" width="34" height="4" rx="2" fill="#08201a" opacity="0.45" />
            <rect x="12" y="43" width="40" height="5" rx="2.5" fill="#ffffff" opacity="0.28" />
            <rect x="14" y="51" width="36" height="5" rx="2.5" fill="#ffffff" opacity="0.14" />
            @break

        @default
            <circle cx="32" cy="32" r="19" stroke="#ffffff" stroke-opacity="0.12" stroke-width="3.2" />
            <circle cx="32" cy="32" r="19" stroke="#f2b72f" stroke-width="3.2" stroke-dasharray="88 32" stroke-linecap="round" transform="rotate(-90 32 32)" />
            <text x="32" y="40" text-anchor="middle" font-size="22" font-weight="600" fill="#f5c955" font-family="Fraunces, Georgia, serif">3</text>
    @endswitch
</svg>
