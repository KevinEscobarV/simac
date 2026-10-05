<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        {{-- For keyboards: the first Tab offers to jump over the navigation. --}}
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:inset-s-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-brand-900 focus:shadow-raised focus:outline-2 focus:outline-gold-400"
        >
            {{ __('Skip to content') }}
        </a>

        {{-- The sidebar is always dark green: the "dark" class scopes Flux's dark variants to it. --}}
        <flux:sidebar sticky collapsible="mobile" class="app-sidebar dark bg-institutional border-e border-white/10 lg:w-66">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="rounded-xl px-1 py-2" wire:navigate>
                    <x-brand.logo />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @if (auth()->user()->homeRoute() === 'dashboard')
                <flux:sidebar.nav class="mt-4">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Home') }}
                    </flux:sidebar.item>
                </flux:sidebar.nav>
            @endif

            @can('viewAny', App\Models\City::class)
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Roll')">
                        <flux:sidebar.item icon="academic-cap" :href="route('teachers.index')" :current="request()->routeIs('teachers.index')" wire:navigate>
                            {{ __('Teachers') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="identification" :href="route('teachers.cards')" :current="request()->routeIs('teachers.cards')" wire:navigate>
                            {{ __('Cards') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')" wire:navigate>
                            {{ __('Municipalities and schools') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            @canany(['viewAny', 'useDesk'], App\Models\Assembly::class)
                <flux:sidebar.nav @class(['mt-4' => auth()->user()->homeRoute() !== 'dashboard'])>
                    <flux:sidebar.group :heading="__('Assembly')">
                        @can('viewAny', App\Models\Assembly::class)
                            <flux:sidebar.item icon="calendar-days" :href="route('assemblies.index')" :current="request()->routeIs('assemblies.*')" wire:navigate>
                                {{ __('Assemblies') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('useDesk', App\Models\Assembly::class)
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('desk')" :current="request()->routeIs('desk')" wire:navigate>
                                {{ __('Registration desk') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            @if (auth()->user()->can('create', App\Models\Raffle::class) || auth()->user()->can('viewAny', App\Models\Raffle::class) || auth()->user()->can('watch', App\Models\Projection::class))
                <flux:sidebar.nav @class(['mt-4' => auth()->user()->homeRoute() !== 'dashboard'])>
                    <flux:sidebar.group :heading="__('Raffles')">
                        @can('create', App\Models\Raffle::class)
                            <flux:sidebar.item icon="gift" :href="route('raffles.create')" :current="request()->routeIs('raffles.create')" wire:navigate>
                                <span class="flex items-center justify-between gap-2">
                                    {{ __('New raffle') }}
                                    <livewire:raffles.live-badge />
                                </span>
                            </flux:sidebar.item>
                        @endcan

                        @can('viewAny', App\Models\Raffle::class)
                            <flux:sidebar.item icon="document-text" :href="route('raffles.index')" :current="request()->routeIs('raffles.index', 'raffles.show')" wire:navigate>
                                {{ __('History') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('watch', App\Models\Projection::class)
                            {{-- In a tab of its own: it is meant for the projector. --}}
                            <flux:sidebar.item icon="tv" :href="route('screen')" target="_blank">
                                {{ __('Projection screen') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

            @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('manage', App\Models\Setting::class))
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Administration')">
                        @can('viewAny', App\Models\User::class)
                            <flux:sidebar.item icon="user-group" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                {{ __('Users') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage', App\Models\Setting::class)
                            <flux:sidebar.item icon="cog-6-tooth" :href="route('configuration')" :current="request()->routeIs('configuration')" wire:navigate>
                                {{ __('Configuration') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

            <flux:spacer />

            <p class="px-1 text-[0.65rem] leading-relaxed text-white/50">
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
