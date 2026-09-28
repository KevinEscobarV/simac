@props([
    'teacher',
])

{{-- A retired teacher's membership can no longer be switched. --}}
@if ($teacher->trashed())
    <flux:badge size="sm" :color="$teacher->is_union_member ? 'emerald' : 'zinc'" {{ $attributes }}>
        {{ $teacher->is_union_member ? __('Member') : __('Not a member') }}
    </flux:badge>
@else
    <flux:badge
        as="button"
        size="sm"
        :color="$teacher->is_union_member ? 'emerald' : 'zinc'"
        :icon="$teacher->is_union_member ? 'check' : 'x-mark'"
        wire:click="toggleMembership({{ $teacher->id }})"
        :title="__('Change membership')"
        {{ $attributes }}
    >
        {{ $teacher->is_union_member ? __('Member') : __('Not a member') }}
    </flux:badge>
@endif
