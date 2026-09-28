@props([
    'code',
    'title',
    'message',
])

{{--
    An error page with the brand. It may render before the session starts
    (a page that does not exist), so it asks nothing about the user: "Home"
    takes each one to their post, or to sign in.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(rgb(19_35_29/0.07)_1px,transparent_1px)] bg-size-[24px_24px] dark:bg-dots"></div>
            <div class="pointer-events-none absolute -top-48 left-1/2 size-[38rem] -translate-x-1/2 rounded-full bg-brand-500/12 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-56 left-1/3 size-[30rem] rounded-full bg-gold-400/10 blur-3xl"></div>

            <main class="relative w-full max-w-md animate-rise text-center">
                <x-brand.logo surface="light" class="justify-center" />

                <div class="mt-12 bg-linear-to-b from-brand-800 to-brand-600 bg-clip-text font-display text-[7rem] leading-none font-semibold tracking-tight text-transparent tabular-nums dark:from-gold-200 dark:to-gold-500">
                    {{ $code }}
                </div>

                <h1 class="mt-4 font-display text-2xl font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
                <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $message }}</p>

                <div class="mt-8 flex flex-wrap justify-center gap-2.5">
                    <flux:button variant="primary" icon="home" :href="route('home')">{{ __('Go home') }}</flux:button>
                    <flux:button variant="ghost" icon="arrow-left" onclick="history.back()">{{ __('Go back') }}</flux:button>
                </div>

                <p class="mt-12 text-xs text-zinc-400 dark:text-zinc-500">{{ __('Casanare Teachers\' Union') }}</p>
            </main>
        </div>
    </body>
</html>
