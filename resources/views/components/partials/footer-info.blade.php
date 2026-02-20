<footer {{ $attributes->merge(['class' => 'mt-auto text-center']) }}>
    <x-mary-menu-separator />
    <p class="text-xs text-base-content/70">
        &copy; {{ date('Y') }} <span class="font-bold">{{ config('app.name', 'Laravel') }}</span>. By
        <a href="https://github.com/moko-github/" target="_blank" rel="noopener noreferrer"
            class="text-primary hover:underline">Moko</a>.
    </p>
</footer>
