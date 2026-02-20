<div class="min-h-screen flex">
    @include('layouts.app.sidebar')

    <div class="flex-1 flex flex-col">
        @include('layouts.app.header')

        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
</div>
