@props([
    'size' => 40,
])

{{--
    SIMAC mark: a graduation cap over a rounded shield.
    Gradient ids are unique per render: a mark hidden with display:none would
    otherwise break every other mark that points to its gradients.
--}}
@php
    $id = 'mark-'.Str::random(8);
@endphp

<svg
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 48 48"
    fill="none"
    aria-hidden="true"
    {{ $attributes->class('shrink-0') }}
>
    <defs>
        <linearGradient id="{{ $id }}-fill" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#2f8963" />
            <stop offset="55%" stop-color="#195840" />
            <stop offset="100%" stop-color="#08201a" />
        </linearGradient>
        <linearGradient id="{{ $id }}-shine" x1="0" y1="0" x2="0.4" y2="1">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="14" fill="url(#{{ $id }}-fill)" />
    <rect width="48" height="48" rx="14" fill="url(#{{ $id }}-shine)" />
    <rect x="0.75" y="0.75" width="46.5" height="46.5" rx="13.25" stroke="#f2b72f" stroke-opacity="0.35" stroke-width="1.5" />

    <path d="M24 11.5 38.5 18 24 24.5 9.5 18 24 11.5Z" fill="#f5c955" />
    <path d="M24 11.5 38.5 18 24 24.5 9.5 18 24 11.5Z" fill="#ffffff" fill-opacity="0.14" />
    <path d="M16.8 21.2v5.6c0 2.7 3.2 4.8 7.2 4.8s7.2-2.1 7.2-4.8v-5.6" stroke="#f2b72f" stroke-width="2.6" stroke-linecap="round" />
    <path d="M35.2 19.4v6.8" stroke="#f2b72f" stroke-width="2.2" stroke-linecap="round" />
    <circle cx="35.2" cy="28.4" r="2" fill="#f2b72f" />
</svg>
