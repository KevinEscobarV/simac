@props([
    'reel',
    'winnerIndex',
    'attempt',
    'landed' => false,
])

{{-- The name drum: five rows, the middle one highlighted, the outer ones fading out. --}}
<div
    x-data="drum(@js(['names' => array_column($reel, 'name'), 'winner' => $winnerIndex, 'attempt' => $attempt, 'landed' => $landed]))"
    class="w-full max-w-3xl px-4"
>
    <div class="relative">
        <div class="absolute -inset-6 rounded-[2.5rem] bg-gold-400/12 blur-3xl transition-opacity duration-700" :class="stopped ? 'opacity-100' : 'opacity-40'"></div>

        <div class="relative overflow-hidden rounded-4xl border border-white/10 bg-white/5 shadow-[0_30px_80px_-20px_rgb(0_0_0/0.7)] backdrop-blur-xl">
            <div class="flex flex-col items-center py-4 [mask-image:linear-gradient(to_bottom,transparent_0%,#000_18%,#000_82%,transparent_100%)]">
                @foreach ([-2 => 'h-14 text-lg opacity-15 md:text-2xl short:h-10', -1 => 'h-16 text-2xl opacity-40 md:text-3xl short:h-12'] as $offset => $row)
                    <div class="flex w-full items-center justify-center px-6 {{ $row }}">
                        <span class="truncate font-semibold" x-text="at({{ $offset }})"></span>
                    </div>
                @endforeach

                <div class="flex h-24 w-full items-center justify-center px-6 short:h-20">
                    <div
                        class="flex w-full items-center justify-center rounded-2xl border px-6 py-3 transition-all duration-300"
                        :class="stopped ? 'scale-[1.03] border-gold-400/80 bg-gold-400/12 shadow-[0_0_45px_rgb(242_183_47/0.35)]' : 'border-white/14 bg-white/5'"
                    >
                        <span
                            class="truncate text-center text-4xl leading-tight font-extrabold tracking-tight transition-colors duration-300 md:text-6xl short:md:text-5xl"
                            :class="stopped ? 'text-gold-300' : 'text-white'"
                            x-text="at(0)"
                        ></span>
                    </div>
                </div>

                @foreach ([1 => 'h-16 text-2xl opacity-40 md:text-3xl short:h-12', 2 => 'h-14 text-lg opacity-15 md:text-2xl short:h-10'] as $offset => $row)
                    <div class="flex w-full items-center justify-center px-6 {{ $row }}">
                        <span class="truncate font-semibold" x-text="at({{ $offset }})"></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
