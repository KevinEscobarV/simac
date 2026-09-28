@props([
    'winner',
    'raffle',
    'position',
])

<div {{ $attributes->class('animate-pop px-4') }}>
    <div class="rounded-3xl bg-linear-to-b from-gold-300/80 via-gold-500/35 to-gold-400/55 p-px shadow-[0_34px_90px_-28px_rgb(242_183_47/0.55)]">
        <div class="rounded-3xl bg-stage-900/88 px-7 py-6 text-center backdrop-blur-2xl sm:px-14 sm:py-8 short:py-5">
            <div class="mb-4 flex justify-center short:hidden">
                <div class="relative">
                    <div class="absolute inset-0 rounded-full bg-gold-400/30 blur-lg"></div>
                    <flux:avatar size="xl" :name="$winner->name" class="relative ring-2 ring-gold-300/70" />
                    <span class="absolute -end-1.5 -bottom-1.5 flex size-8 items-center justify-center rounded-full bg-gold-400 text-brand-950 shadow-gold">
                        <flux:icon.trophy variant="mini" class="size-4.5" />
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-center gap-2.5 text-gold-300">
                <flux:icon.sparkles variant="micro" class="size-3.5" />
                <span class="text-[0.72rem] font-semibold tracking-[0.42em] uppercase">
                    {{ __('Congratulations!') }}
                    @if ($raffle->winners_count > 1)
                        · {{ __(':position of :total', ['position' => $position, 'total' => $raffle->winners_count]) }}
                    @endif
                </span>
                <flux:icon.sparkles variant="micro" class="size-3.5" />
            </div>

            <h2 class="mt-2 font-display text-4xl font-semibold text-balance text-white sm:text-6xl short:text-5xl">{{ $winner->name }}</h2>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-6 short:mt-2 gap-y-1.5 text-base text-white/60 sm:text-lg">
                <span class="flex items-center gap-2">
                    <flux:icon.academic-cap variant="mini" class="size-4.5" />
                    {{ $winner->school->name }}
                </span>
                <span class="flex items-center gap-2">
                    <flux:icon.map-pin variant="mini" class="size-4.5" />
                    {{ $winner->school->city->name }}
                </span>
            </div>

            <div class="mt-5 flex flex-wrap justify-center gap-1.5 text-xs font-medium short:mt-3">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-gold-400/35 bg-gold-400/12 px-3 py-1 text-gold-100">
                    <flux:icon.gift variant="micro" class="size-3.5" />
                    {{ $raffle->prize }}
                </span>
                @if ($winner->is_union_member)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-400/40 bg-brand-500/20 px-3 py-1 text-brand-100">
                        <flux:icon.shield-check variant="micro" class="size-3.5" />
                        {{ __('Union membership') }}
                    </span>
                @endif
                <span class="rounded-full border border-white/12 bg-white/8 px-3 py-1 text-white/70">{{ __('Record No. :number', ['number' => $raffle->id]) }}</span>
            </div>
        </div>
    </div>
</div>
