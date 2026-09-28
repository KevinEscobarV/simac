@props([
    'icon',
    'title',
    'description',
    'disabled' => false,
])

{{-- A condition of the raffle, switched on or off. A native checkbox underneath, so the whole card is its label. --}}
<label @class([
    'group flex items-center gap-3 rounded-xl border px-4 py-3.5 transition',
    'border-zinc-200 bg-white has-checked:border-brand-300 has-checked:bg-brand-50 dark:border-white/10 dark:bg-zinc-900 dark:has-checked:border-brand-500/40 dark:has-checked:bg-brand-500/10',
    'cursor-pointer hover:border-zinc-300 dark:hover:border-white/20' => ! $disabled,
    'cursor-not-allowed opacity-60' => $disabled,
])>
    <input type="checkbox" class="peer sr-only" @disabled($disabled) {{ $attributes }} />

    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 transition group-has-checked:bg-brand-600 group-has-checked:text-white dark:bg-white/5 dark:text-zinc-500">
        <flux:icon :icon="$icon" variant="mini" class="size-4.5" />
    </span>

    <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</span>
        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
    </span>

    <span class="relative h-6 w-11 shrink-0 rounded-full bg-zinc-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-accent peer-focus-visible:ring-offset-2 dark:bg-white/15 dark:peer-checked:bg-brand-500 dark:peer-focus-visible:ring-offset-zinc-900" aria-hidden="true">
        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-sm transition-transform group-has-checked:translate-x-5"></span>
    </span>
</label>
