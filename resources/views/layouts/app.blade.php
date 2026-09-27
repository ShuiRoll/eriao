<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="flex flex-col w-full h-full">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
