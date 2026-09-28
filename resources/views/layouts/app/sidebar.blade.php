<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        {{-- The sidebar is always dark green: the "dark" class scopes Flux's dark variants to it. --}}
        <flux:sidebar sticky collapsible="mobile" class="app-sidebar dark bg-institutional border-e border-white/10 lg:w-66">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="rounded-xl px-1 py-2" wire:navigate>
                    <x-brand.logo />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="mt-4">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Home') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            @can('viewAny', App\Models\User::class)
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Administration')">
                        <flux:sidebar.item icon="user-group" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            <flux:spacer />

            <p class="px-1 text-[0.65rem] leading-relaxed text-white/35">
                {{ __('Casanare Teachers\' Union') }}
            </p>

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        <flux:header class="dark bg-institutional border-b border-white/10 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2" wire:navigate>
                <x-brand.logo :size="32" />
            </a>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <x-user-menu />
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
