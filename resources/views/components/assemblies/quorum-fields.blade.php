@props([
    'model',
    'form',
    'unionMembers',
])

{{--
    The quorum of an assembly form. The figure always comes with its
    translation into people, because a bare percentage does not say how many
    are needed.
--}}
<div class="space-y-3">
    <flux:radio.group wire:model.live="{{ $model }}.quorum_type" :label="__('Quorum')" variant="segmented">
        <flux:radio value="none" :label="__('No quorum')" />
        <flux:radio value="count" :label="__('Number')" />
        <flux:radio value="percentage" :label="__('Percentage')" />
    </flux:radio.group>

    @if ($form->quorum_type === 'none')
        <flux:text size="sm">{{ __('The assembly works the same, but no quorum indicator is shown.') }}</flux:text>
    @else
        <flux:field>
            <flux:input.group>
                <flux:input
                    wire:model.live.debounce.300ms="{{ $model }}.quorum_value"
                    type="number"
                    inputmode="decimal"
                    min="1"
                    :max="$form->quorum_type === 'percentage' ? 100 : null"
                    :aria-label="__('Quorum value')"
                />
                <flux:input.group.suffix>
                    {{ $form->quorum_type === 'percentage' ? __('% of members') : __('members') }}
                </flux:input.group.suffix>
            </flux:input.group>

            <flux:error name="{{ $model }}.quorum_value" />
        </flux:field>

        @php($required = $form->requiredMembers($unionMembers))

        @if ($required !== null)
            @if ($required > $unionMembers)
                <p class="flex items-start gap-2 text-xs text-red-600 dark:text-red-400">
                    <flux:icon.exclamation-triangle variant="micro" class="mt-px size-3.5 shrink-0" />
                    {{ __(':required members are required, but the roll only has :count: the quorum would never be reached.', ['required' => $required, 'count' => $unionMembers]) }}
                </p>
            @else
                <p class="flex items-start gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <flux:icon.users variant="micro" class="mt-px size-3.5 shrink-0" />
                    {{ __(':required of the :count union members will need to be in the room.', ['required' => $required, 'count' => $unionMembers]) }}
                </p>
            @endif
        @endif
    @endif
</div>
