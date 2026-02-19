<a href="/" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <x-app-logo-icon class="size-7 fill-current" />
    <span class="font-semibold text-lg">{{ config('app.name', 'Laravel') }}</span>
</a>
