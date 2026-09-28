@props([
    'icon',
    'title',
    'message' => null,
])

<div {{ $attributes->class('flex flex-col items-center px-6 py-14 text-center') }}>
    <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-white/5 dark:text-zinc-500">
        <flux:icon :icon="$icon" class="size-6" />
    </span>

    <h3 class="mt-4 font-display text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>

    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
