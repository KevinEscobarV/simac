@props([
    'attendance',
])

@if ($attendance === null)
    <span {{ $attributes->class('inline-flex items-center rounded-md border border-dashed border-zinc-300 px-2 py-0.5 text-xs font-medium text-zinc-400 dark:border-white/15 dark:text-zinc-500') }}>
        {{ __('Not registered') }}
    </span>
@elseif ($attendance->isPresent())
    <flux:badge size="sm" color="emerald" {{ $attributes }}>{{ __('Present') }}</flux:badge>
@else
    <flux:badge size="sm" color="zinc" {{ $attributes }}>{{ __('Left') }}</flux:badge>
@endif
