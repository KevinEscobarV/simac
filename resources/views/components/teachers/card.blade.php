@props([
    'teacher',
])

{{--
    A teacher's card, at the size of an ID card (85.6 × 54 mm) both on screen
    and on paper. Its barcode is what the desk reads. Colors are kept when
    printing, even if the browser leaves backgrounds out.
--}}
<article {{ $attributes->class('relative flex h-[54mm] w-[85.6mm] shrink-0 flex-col overflow-hidden rounded-[3mm] border border-zinc-300 bg-white text-zinc-900 [print-color-adjust:exact] break-inside-avoid') }}>
    <header class="relative flex h-[14mm] items-center gap-[2.5mm] overflow-hidden bg-brand-950 px-[4mm] text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(rgb(255_255_255/0.07)_1px,transparent_1px)] bg-size-[3mm_3mm]"></div>
        <div class="pointer-events-none absolute -top-[10mm] -right-[6mm] size-[26mm] rounded-full bg-gold-400/20 blur-xl"></div>

        <x-brand.mark :size="30" class="relative" />

        <div class="relative min-w-0 flex-1 leading-tight">
            <div class="truncate text-[5.6pt] font-semibold tracking-[0.18em] text-gold-300 uppercase">{{ __('Casanare Teachers\' Union') }}</div>
            <div class="mt-[0.6mm] font-display text-[9.5pt] font-semibold tracking-[0.12em]">SIMAC <span class="font-sans text-[6.5pt] font-medium tracking-normal text-white/60">· {{ __('Teacher card') }}</span></div>
        </div>
    </header>
    <div class="h-[0.7mm] bg-gold-400"></div>

    <div class="flex min-h-0 flex-1 flex-col justify-between px-[4mm] pt-[2.6mm] pb-[2.4mm]">
        <div class="flex items-start gap-[3mm]">
            <div class="min-w-0 flex-1">
                <div class="line-clamp-2 font-display text-[11pt] leading-[1.15] font-semibold text-brand-950">{{ $teacher->name }}</div>
                <div class="mt-[1mm] truncate text-[6.8pt] text-zinc-600">{{ $teacher->school->name }} · {{ $teacher->school->city->name }}</div>
            </div>

            @if ($teacher->is_union_member)
                <span class="mt-[0.4mm] shrink-0 rounded-full border border-gold-500/60 bg-gold-50 px-[1.8mm] py-[0.5mm] text-[5.6pt] font-semibold tracking-wide text-gold-800 uppercase">
                    {{ __('Union membership') }}
                </span>
            @endif
        </div>

        <div class="flex items-end gap-[3mm]">
            <x-teachers.barcode :value="$teacher->code" class="h-[11mm] w-[52mm] shrink-0" />

            <div class="min-w-0 flex-1 text-right leading-none">
                <div class="text-[5.2pt] font-semibold tracking-[0.2em] text-zinc-500 uppercase">{{ __('Code') }}</div>
                <div class="mt-[1mm] font-display text-[12.5pt] font-semibold tracking-wide text-brand-900 tabular-nums">{{ $teacher->code }}</div>
            </div>
        </div>
    </div>
</article>
