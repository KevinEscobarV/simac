@props([
    'count',
])

{{-- For dark surfaces. Before giving the order, the first thing to check is whether the projector is listening. --}}
@if ($count > 0)
    <span {{ $attributes->class('inline-flex items-center gap-2 rounded-full border border-brand-400/30 bg-brand-500/15 px-2.5 py-1 text-xs font-medium text-brand-100') }}>
        <span class="relative flex size-2">
            <span class="absolute inline-flex size-full animate-breathe rounded-full bg-brand-300"></span>
            <span class="relative inline-flex size-2 rounded-full bg-brand-300"></span>
        </span>
        {{ trans_choice('{1} :count screen connected|[2,*] :count screens connected', $count) }}
    </span>
@else
    <span {{ $attributes->class('inline-flex items-center gap-2 rounded-full border border-gold-400/40 bg-gold-400/15 px-2.5 py-1 text-xs font-medium text-gold-200') }}>
        <flux:icon.exclamation-triangle variant="micro" class="size-3.5" />
        {{ __('No screen connected') }}
    </span>
@endif
