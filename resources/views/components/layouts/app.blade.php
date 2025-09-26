<x-layouts.app.header :title="$title ?? null">
    <flux:main class="!p-0">
        {{ $slot }}
    </flux:main>
</x-layouts.app.header>
