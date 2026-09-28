@props([
    'title',
    'description' => null,
])

<header {{ $attributes->class('mb-7 flex flex-wrap items-end justify-between gap-4') }}>
    <div class="min-w-0">
        <h1 class="font-display text-3xl font-semibold tracking-tight text-zinc-900 sm:text-[2rem] dark:text-white">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2.5">
            {{ $actions }}
        </div>
    @endisset
</header>
