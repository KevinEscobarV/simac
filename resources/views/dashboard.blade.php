<x-layouts::app :title="__('Home')">
    <x-page-header
        :title="__('Home')"
        :description="__('Welcome, :name.', ['name' => auth()->user()->name])"
    />

    <section class="dark bg-institutional animate-rise overflow-hidden rounded-2xl border border-brand-800 p-6 text-white shadow-raised sm:p-8">
        <div class="text-[0.68rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">
            {{ now()->translatedFormat('l, j \d\e F') }}
        </div>

        <h2 class="mt-2 max-w-2xl font-display text-2xl leading-snug font-semibold text-balance sm:text-3xl">
            {{ __('Roll, attendance and raffles') }}
            <span class="block text-gold-300">{{ __('with verifiable records.') }}</span>
        </h2>

        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/60">
            {{ __('From here you will manage the teachers\' roll, the assemblies and the raffles of the union.') }}
        </p>
    </section>
</x-layouts::app>
