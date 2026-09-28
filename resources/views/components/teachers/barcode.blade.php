@props([
    'value',
])

{{--
    A Code 128 barcode, the symbology every scanner reads by default. The
    bars are drawn in modules and stretched to the box the SVG is given; ten
    blank modules on each side are the quiet zone scanners need.
--}}
@php
    $barcode = (new Picqer\Barcode\Types\TypeCode128)->getBarcode($value);
    $quietZone = 10;
    $position = 0;
@endphp

<svg
    viewBox="-{{ $quietZone }} 0 {{ $barcode->getWidth() + 2 * $quietZone }} 10"
    preserveAspectRatio="none"
    shape-rendering="crispEdges"
    role="img"
    aria-label="{{ __('Barcode :value', ['value' => $value]) }}"
    {{ $attributes }}
>
    @foreach ($barcode->getBars() as $bar)
        @if ($bar->isBar())
            <rect x="{{ $position }}" width="{{ $bar->getWidth() }}" height="10" fill="#000" />
        @endif
        @php($position += $bar->getWidth())
    @endforeach
</svg>
