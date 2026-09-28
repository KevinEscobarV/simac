<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main container id="main" tabindex="-1" class="max-w-6xl py-7 focus:outline-none lg:py-10">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
