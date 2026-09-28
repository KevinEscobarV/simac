{{--
    The cards to print: letter sheets of eight, at real size. On screen each
    sheet looks like paper, with a bar to print; on paper only the cards come
    out. The print dialog opens by itself.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['appearance' => false, 'title' => __('Cards to print')])

        <style>
            @page {
                size: letter;
                margin: 12mm;
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-200 text-zinc-900 print:bg-white">
        <header class="sticky top-0 z-10 border-b border-white/10 bg-institutional text-white print:hidden">
            <div class="mx-auto flex max-w-[215.9mm] flex-wrap items-center gap-4 px-4 py-3">
                <x-brand.mark :size="32" />

                <div class="min-w-0 flex-1">
                    <div class="font-display text-lg leading-tight font-semibold">{{ __('Cards to print') }}</div>
                    <div class="text-xs text-white/55">
                        {{ trans_choice('{0} No cards|{1} :count card|[2,*] :count cards', $count) }}
                        @if ($count > 0)
                            · {{ trans_choice('{1} :count letter sheet|[2,*] :count letter sheets', $sheets->count()) }}
                        @endif
                    </div>
                </div>

                @if ($count > 0)
                    <x-gold-button icon="printer" onclick="window.print()" class="py-2.5 text-sm">{{ __('Print') }}</x-gold-button>
                @endif
            </div>

            @if ($count > 0)
                <p class="mx-auto max-w-[215.9mm] px-4 pb-3 text-xs text-white/50">
                    {{ __('In the print dialog choose letter size, 100% scale and "Background graphics". Then cut along the edge of each card.') }}
                </p>
            @endif
        </header>

        <main class="py-8 print:py-0">
            @forelse ($sheets as $sheet)
                <section
                    data-sheet
                    class="mx-auto mb-8 grid min-h-[279.4mm] w-[215.9mm] auto-rows-[54mm] grid-cols-[repeat(2,85.6mm)] content-start justify-center gap-x-[8mm] gap-y-[6mm] bg-white p-[12mm] shadow-raised not-last:break-after-page print:m-0 print:min-h-0 print:w-auto print:p-0 print:shadow-none"
                >
                    @foreach ($sheet as $teacher)
                        <x-teachers.card :teacher="$teacher" />
                    @endforeach
                </section>
            @empty
                <div class="mx-auto max-w-md rounded-2xl bg-white p-8 text-center shadow-card">
                    <h1 class="font-display text-xl font-semibold">{{ __('No cards to print') }}</h1>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('No active teacher meets the filters. Try loosening them.') }}</p>
                </div>
            @endforelse
        </main>

        @if ($count > 0)
            <script>
                window.addEventListener('load', () => setTimeout(() => window.print(), 400));
            </script>
        @endif
    </body>
</html>
