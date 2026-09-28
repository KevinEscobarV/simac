@props([
    'user',
])

@if ($user->isDeactivated())
    <flux:tooltip :content="__('Since :date', ['date' => $user->deactivated_at->translatedFormat('j M Y')])">
        <flux:badge size="sm" color="zinc" icon="no-symbol">{{ __('Deactivated') }}</flux:badge>
    </flux:tooltip>
@else
    <span class="inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
        <span class="size-1.5 rounded-full bg-brand-500"></span>
        {{ __('Active') }}
    </span>
@endif
