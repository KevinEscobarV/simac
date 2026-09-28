@props([
    'quorum',
    'adjustable' => false,
])

{{-- For dark surfaces: the assembly card here, the registration desk later. --}}
@if ($quorum === null)
    <div {{ $attributes->class('flex items-center gap-3 rounded-xl border border-dashed border-white/15 bg-white/5 px-4 py-3') }}>
        <flux:icon.shield-check variant="mini" class="size-4.5 shrink-0 text-white/30" />
        <span class="flex-1 text-sm text-white/55">{{ __('This assembly has no quorum set.') }}</span>

        @if ($adjustable)
            <flux:button size="sm" variant="ghost" wire:click="editQuorum">{{ __('Set quorum') }}</flux:button>
        @endif
    </div>
@else
    @php($met = $quorum->isMet())

    <div {{ $attributes->class([
        'rounded-xl border px-4 py-3.5',
        'border-brand-400/35 bg-brand-500/12' => $met,
        'border-gold-400/35 bg-gold-400/10' => ! $met,
    ]) }}>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <span class="text-[0.66rem] font-semibold tracking-[0.2em] text-white/45 uppercase">{{ __('Quorum') }}</span>

            <span @class([
                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold',
                'bg-brand-500/25 text-brand-100' => $met,
                'bg-gold-400/20 text-gold-100' => ! $met,
            ])>
                <flux:icon :icon="$met ? 'check' : 'exclamation-triangle'" variant="micro" class="size-3.5" />
                {{ $met ? __('Reached') : trans_choice('{1} :count member missing|[2,*] :count members missing', $quorum->missing()) }}
            </span>

            <span class="ms-auto flex items-baseline gap-1">
                <span @class(['font-display text-xl leading-none font-semibold tabular-nums', 'text-brand-200' => $met, 'text-gold-200' => ! $met])>
                    {{ $quorum->presentMembers }}
                </span>
                <span class="text-sm text-white/40">/ {{ $quorum->required() }}</span>
            </span>

            @if ($adjustable)
                <flux:tooltip :content="__('Adjust the quorum')">
                    <flux:button size="xs" variant="ghost" icon="adjustments-horizontal" wire:click="editQuorum" :aria-label="__('Adjust the quorum')" />
                </flux:tooltip>
            @endif
        </div>

        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $quorum->required() }}" aria-valuenow="{{ $quorum->presentMembers }}">
            <div
                @class(['h-full rounded-full transition-[width] duration-700', 'bg-brand-400' => $met, 'bg-gold-400' => ! $met])
                style="width: {{ max(3, round($quorum->progress() * 100)) }}%"
            ></div>
        </div>

        <div class="mt-2 text-xs text-white/45">
            {{ __('Union members present') }} ·
            @if ($quorum->type === App\Enums\QuorumType::Percentage)
                {{ __(':percent% of :count members', ['percent' => Illuminate\Support\Number::trim($quorum->value), 'count' => $quorum->unionMembers]) }}
            @else
                {{ trans_choice('{1} :count member required|[2,*] :count members required', $quorum->required()) }}
            @endif
        </div>

        @unless ($quorum->isReachable())
            <div class="mt-2 flex items-start gap-1.5 text-xs text-gold-200">
                <flux:icon.exclamation-triangle variant="micro" class="mt-px size-3.5 shrink-0" />
                {{ __('The roll only has :count union members: this quorum cannot be reached.', ['count' => $quorum->unionMembers]) }}
            </div>
        @endunless
    </div>
@endif
