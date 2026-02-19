<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR --}}
    <x-nav sticky>
        <x-slot:brand>
            <a href="/" class="flex items-center gap-2">
                <x-app-logo-icon class="size-7 fill-current" />
                <span class="font-semibold text-lg">{{ config('app.name', 'Laravel') }}</span>
            </a>
        </x-slot:brand>
        <x-slot:actions>
            <x-theme-toggle class="btn btn-circle btn-ghost btn-sm" />
            @if (Route::has('login'))
                @auth
                    <x-button label="Dashboard" link="{{ url('/dashboard') }}" class="btn-primary btn-sm" icon="o-home" />
                @else
                    <x-button label="Log in" link="{{ route('login') }}" class="btn-ghost btn-sm" />
                    @if (Route::has('register'))
                        <x-button label="Register" link="{{ route('register') }}" class="btn-primary btn-sm" />
                    @endif
                @endauth
            @endif
        </x-slot:actions>
    </x-nav>

    {{-- HERO --}}
    <div class="hero min-h-[80vh]">
        <div class="hero-content text-center">
            <div class="max-w-2xl">
                <div class="flex justify-center mb-6">
                    <x-app-logo-icon class="size-20 fill-current text-primary" />
                </div>
                <h1 class="text-5xl font-bold">{{ config('app.name', 'Laravel') }}</h1>
                <p class="py-6 text-lg text-base-content/70">
                    {{ __('Laravel has an incredibly rich ecosystem. Here are some resources to get you started.') }}
                </p>

                <div class="flex flex-wrap justify-center gap-3 mb-10">
                    @auth
                        <x-button label="Dashboard" link="{{ url('/dashboard') }}" class="btn-primary" icon="o-home" />
                    @else
                        @if (Route::has('login'))
                            <x-button label="Log in" link="{{ route('login') }}" class="btn-primary" icon="o-arrow-right-on-rectangle" />
                        @endif
                        @if (Route::has('register'))
                            <x-button label="Register" link="{{ route('register') }}" class="btn-outline" icon="o-user-plus" />
                        @endif
                    @endauth
                </div>

                {{-- Resource cards --}}
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 text-left">
                    <x-card title="Documentation" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Laravel has wonderful documentation covering every aspect of the framework.') }}</p>
                        <x-slot:figure>
                            <div class="bg-primary/10 p-6 flex justify-center">
                                <x-icon name="o-book-open" class="w-10 h-10 text-primary" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-button label="Read docs" link="https://laravel.com/docs" external class="btn-sm btn-primary" />
                        </x-slot:actions>
                    </x-card>

                    <x-card title="Laracasts" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Watch thousands of video tutorials on Laravel, PHP, and JavaScript.') }}</p>
                        <x-slot:figure>
                            <div class="bg-secondary/10 p-6 flex justify-center">
                                <x-icon name="o-play-circle" class="w-10 h-10 text-secondary" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-button label="Watch now" link="https://laracasts.com" external class="btn-sm btn-secondary" />
                        </x-slot:actions>
                    </x-card>

                    <x-card title="MaryUI" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Beautiful UI components for Laravel built with Livewire 3, DaisyUI and Tailwind.') }}</p>
                        <x-slot:figure>
                            <div class="bg-accent/10 p-6 flex justify-center">
                                <x-icon name="o-sparkles" class="w-10 h-10 text-accent" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-button label="Explore" link="https://mary-ui.com" external class="btn-sm btn-accent" />
                        </x-slot:actions>
                    </x-card>
                </div>
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="footer footer-center p-6 text-base-content/60 text-sm">
        <div>
            <p>Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})</p>
        </div>
    </footer>

    <x-toast />
</body>
</html>
