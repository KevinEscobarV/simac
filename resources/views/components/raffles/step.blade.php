@props([
    'number',
    'title',
])

<section {{ $attributes->class('rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card sm:p-6 dark:border-white/10 dark:bg-zinc-900') }}>
    <header class="mb-5 flex items-center gap-3">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-800 font-display text-sm font-semibold text-gold-300">{{ $number }}</span>
        <h2 class="font-display text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
    </header>

    {{ $slot }}
</section>
