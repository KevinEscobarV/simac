<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main container class="max-w-6xl py-7 lg:py-10">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
