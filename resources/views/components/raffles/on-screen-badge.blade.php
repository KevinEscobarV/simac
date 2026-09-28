{{-- The raffle is on the screens: its winners come out one by one. --}}
<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full border border-gold-400/40 bg-gold-400/15 px-2 py-0.5 text-[0.7rem] font-semibold text-gold-700 dark:text-gold-200') }}>
    <span class="relative flex size-1.5">
        <span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-400 opacity-60"></span>
        <span class="relative inline-flex size-1.5 rounded-full bg-gold-400"></span>
    </span>
    {{ __('On screen') }}
</span>
