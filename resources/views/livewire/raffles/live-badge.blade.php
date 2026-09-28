<span>
    @if ($live)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-400/20 px-2 py-0.5 text-[0.6rem] font-semibold tracking-wide text-gold-200">
            <span class="relative flex size-1.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-300 opacity-60"></span>
                <span class="relative inline-flex size-1.5 rounded-full bg-gold-300"></span>
            </span>
            {{ __('LIVE') }}
        </span>
    @endif
</span>
