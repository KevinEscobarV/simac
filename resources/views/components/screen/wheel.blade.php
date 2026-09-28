@props([
    'reel',
    'winnerIndex',
    'attempt',
    'landed' => false,
])

{{--
    The wheel. Slices and names are drawn here; Alpine only turns it until
    the pointer (at the top) stops on the winner's slice. Names are shown up
    to 40 slices: beyond that they no longer fit.
--}}
@php
    $count = count($reel);
    $slice = 360 / $count;
    $center = 240;
    $radius = 214;
    $colors = ['#16a67b', '#f2b72f', '#e0685f', '#4a9be0', '#9b6be0', '#e8913c'];
    $xy = fn (float $angle, float $length): array => [round($center + $length * cos(deg2rad($angle)), 2), round($center + $length * sin(deg2rad($angle)), 2)];
    $point = fn (float $angle, float $length): string => implode(' ', $xy($angle, $length));
    // On the left half the name is turned around, so it never reads upside down.
    $flipped = fn (float $angle): bool => fmod($angle, 360) > 90 && fmod($angle, 360) < 270;
    $fontSize = match (true) {
        $count <= 8 => 19,
        $count <= 14 => 15,
        $count <= 22 => 12.5,
        default => 10,
    };
    $id = 'wheel-'.$attempt;
@endphp

<div
    x-data="wheel(@js(['slices' => $count, 'winner' => $winnerIndex, 'attempt' => $attempt, 'landed' => $landed]))"
    class="relative flex shrink-0 items-center justify-center transition-all duration-700 ease-out"
    :style="done ? 'width: min(38vmin, 360px); height: min(38vmin, 360px)' : 'width: min(70vmin, 540px); height: min(70vmin, 540px)'"
    style="width: min(70vmin, 540px); height: min(70vmin, 540px)"
>
    <div class="absolute -inset-10 rounded-full bg-gold-400/15 blur-3xl transition-all duration-1000" :class="done ? 'opacity-100 scale-110' : 'opacity-45'"></div>

    <svg viewBox="0 0 480 480" class="relative size-full drop-shadow-[0_25px_60px_rgba(0,0,0,0.55)]" :style="style">
        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="#04120d" />

        @foreach ($reel as $index => $entry)
            @php
                $from = $index * $slice;
                $middle = $from + $slice / 2;
            @endphp

            <path
                d="M {{ $center }} {{ $center }} L {{ $point($from, $radius) }} A {{ $radius }} {{ $radius }} 0 {{ $slice > 180 ? 1 : 0 }} 1 {{ $point($from + $slice, $radius) }} Z"
                fill="{{ $colors[$index % count($colors)] }}"
                fill-opacity="{{ $index % 2 ? 0.9 : 1 }}"
                stroke="#04120d"
                stroke-width="1.5"
            />

            @if ($count <= 40)
                <text
                    x="{{ $flipped($middle) ? $center - $radius + 18 : $center + $radius - 18 }}"
                    y="{{ $center + 5 }}"
                    text-anchor="{{ $flipped($middle) ? 'start' : 'end' }}"
                    font-size="{{ $fontSize }}"
                    font-weight="700"
                    fill="#ffffff"
                    style="paint-order: stroke; stroke: rgb(0 0 0 / 0.42); stroke-width: 2.5px"
                    transform="rotate({{ $flipped($middle) ? $middle + 180 : $middle }} {{ $center }} {{ $center }})"
                >{{ $entry['short'] }}</text>
            @endif
        @endforeach
    </svg>

    {{-- The gold rim, the hub with the brand's cap and the pointer do not turn. --}}
    <svg viewBox="0 0 480 480" class="pointer-events-none absolute inset-0 size-full" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $id }}-gold" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f9df8f" />
                <stop offset="45%" stop-color="#f2b72f" />
                <stop offset="100%" stop-color="#9c5211" />
            </linearGradient>
            <radialGradient id="{{ $id }}-hub">
                <stop offset="0%" stop-color="#195840" />
                <stop offset="100%" stop-color="#04120d" />
            </radialGradient>
        </defs>

        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius + 13 }}" fill="none" stroke="url(#{{ $id }}-gold)" stroke-width="13" />
        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius + 6 }}" fill="none" stroke="#04120d" stroke-width="2" opacity="0.55" />

        @for ($peg = 0; $peg < 48; $peg++)
            @php
                [$x1, $y1] = $xy($peg * 7.5, $radius + 8.5);
                [$x2, $y2] = $xy($peg * 7.5, $radius + 17.5);
            @endphp
            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="#04120d" stroke-width="1.6" opacity="0.35" />
        @endfor

        <circle cx="{{ $center }}" cy="{{ $center }}" r="52" fill="url(#{{ $id }}-hub)" />
        <circle cx="{{ $center }}" cy="{{ $center }}" r="52" fill="none" stroke="url(#{{ $id }}-gold)" stroke-width="5" />
        <g transform="translate({{ $center }} {{ $center }}) scale(1.32) translate(-24 -22)">
            <path d="M24 11.5 38.5 18 24 24.5 9.5 18 24 11.5Z" fill="#f5c955" />
            <path d="M16.8 21.2v5.6c0 2.7 3.2 4.8 7.2 4.8s7.2-2.1 7.2-4.8v-5.6" stroke="#f2b72f" stroke-width="2.6" stroke-linecap="round" fill="none" />
            <path d="M35.2 19.4v6.8" stroke="#f2b72f" stroke-width="2.2" stroke-linecap="round" />
            <circle cx="35.2" cy="28.4" r="2" fill="#f2b72f" />
        </g>

        <g class="origin-[240px_34px] transition-transform duration-400" :class="done && 'scale-112'">
            <path d="M240 68 L222 20 A20 20 0 0 1 258 20 Z" fill="url(#{{ $id }}-gold)" stroke="#04120d" stroke-width="2.5" stroke-linejoin="round" />
            <circle cx="{{ $center }}" cy="28" r="7" fill="#04120d" opacity="0.35" />
        </g>
    </svg>
</div>
