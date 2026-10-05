@props([
    'size' => 40,
])

{{--
    SIMAC mark: the union's logo on a white rounded tile, since the picture
    has a white background. The name is always written next to it or read
    aloud elsewhere, so the picture itself is decorative.
--}}
<span
    style="width: {{ $size }}px; height: {{ $size }}px; padding: {{ round($size * 0.08, 1) }}px"
    {{ $attributes->class('inline-flex shrink-0 items-center justify-center rounded-[28%] bg-white shadow-sm ring-1 ring-black/5') }}
>
    <img src="{{ asset('img/simac-logo.jpg') }}" alt="" class="size-full object-contain" draggable="false">
</span>
