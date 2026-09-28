@props([
    'role',
])

@if ($role)
    <flux:badge size="sm" :color="$role->color()" {{ $attributes }}>{{ $role->label() }}</flux:badge>
@else
    <span {{ $attributes->class('text-xs text-zinc-400') }}>{{ __('No role') }}</span>
@endif
