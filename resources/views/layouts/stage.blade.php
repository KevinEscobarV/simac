<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['appearance' => false])
        @vite('resources/js/screen.js')
    </head>
    {{-- The only surface the room sees: always dark, no chrome. --}}
    <body class="min-h-screen overflow-hidden bg-stage-950 text-white">
        {{ $slot }}

        @fluxScripts
    </body>
</html>
