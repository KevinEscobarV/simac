@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center lg:text-start">
    <h1 class="font-display text-[1.75rem] leading-tight font-semibold tracking-tight text-zinc-900 dark:text-white">
        {{ $title }}
    </h1>
    <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
</div>
