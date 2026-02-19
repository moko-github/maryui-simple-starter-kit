@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-xl font-semibold">{{ $title }}</h1>
    <p class="text-sm text-base-content/70 mt-1">{{ $description }}</p>
</div>
