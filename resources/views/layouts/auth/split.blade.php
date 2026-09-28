<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="grid min-h-dvh lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            <aside class="dark bg-institutional hidden flex-col justify-between gap-12 p-10 text-white lg:flex xl:p-14">
                <x-brand.logo :size="44" />

                <div class="max-w-md animate-rise">
                    <h2 class="font-display text-4xl leading-[1.15] font-semibold text-balance xl:text-5xl">
                        {{ __('Roll, attendance and raffles') }}
                        <span class="block text-gold-300">{{ __('with verifiable records.') }}</span>
                    </h2>

                    <p class="mt-5 text-base leading-relaxed text-white/60">
                        {{ __('The Casanare Teachers\' Union system to record assembly attendance and hold transparent raffles.') }}
                    </p>

                    <ul class="mt-10 space-y-4">
                        @foreach ([
                            ['icon' => 'shield-check', 'text' => __('The winner is drawn on the server, never in the browser.')],
                            ['icon' => 'document-check', 'text' => __('Every raffle is sealed in a record before it is shown.')],
                            ['icon' => 'user-group', 'text' => __('Live attendance and quorum during the assembly.')],
                        ] as $feature)
                            <li class="flex items-center gap-3.5 text-sm text-white/75">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-gold-400/25 bg-gold-400/10 text-gold-300">
                                    <flux:icon :icon="$feature['icon']" variant="outline" class="size-4.5" />
                                </span>
                                {{ $feature['text'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-xs text-white/35">
                    &copy; {{ now()->year }} {{ __('Casanare Teachers\' Union') }}
                </p>
            </aside>

            <main class="flex flex-col items-center justify-center bg-[radial-gradient(60%_40%_at_50%_0%,rgb(47_137_99/0.08),transparent_70%)] px-6 py-10 sm:px-10">
                <div class="w-full max-w-sm animate-rise">
                    <a href="{{ route('home') }}" class="mb-10 flex justify-center lg:hidden" wire:navigate>
                        <x-brand.logo surface="light" />
                    </a>

                    {{ $slot }}
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
