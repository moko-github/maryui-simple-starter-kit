<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen font-sans antialiased bg-base-200">

        {{-- NAVBAR --}}
        <x-nav sticky full-width>
            <x-slot:brand>
                <x-app-logo />
            </x-slot:brand>

            <x-slot:actions>
                {{-- Desktop menu --}}
                <div class="hidden lg:flex items-center gap-2">
                    <x-button label="{{ __('Dashboard') }}" icon="o-home" link="{{ route('dashboard') }}" class="btn-ghost btn-sm" responsive />
                    <x-button label="{{ __('Settings') }}" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" class="btn-ghost btn-sm" responsive />
                </div>

                {{-- User dropdown --}}
                @if($user = auth()->user())
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="o-user" class="btn-circle btn-ghost btn-sm" />
                        </x-slot:trigger>

                        <div class="px-3 py-2 text-sm">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-base-content/60">{{ $user->email }}</div>
                        </div>
                        <x-menu-separator />
                        <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" />
                        <x-menu-separator />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-menu-item title="{{ __('Log Out') }}" icon="o-power" data-test="logout-button" onclick="this.closest('form').submit()" />
                        </form>
                    </x-dropdown>
                @endif

                {{-- Mobile hamburger --}}
                <label for="main-drawer" class="lg:hidden">
                    <x-icon name="o-bars-3" class="cursor-pointer" />
                </label>
            </x-slot:actions>
        </x-nav>

        {{-- MAIN with mobile drawer --}}
        <x-main full-width>
            <x-slot:sidebar drawer="main-drawer" class="bg-base-100 lg:hidden">
                <x-menu activate-by-route>
                    @if($user = auth()->user())
                        <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded" />
                        <x-menu-separator />
                    @endif
                    <x-menu-item title="Dashboard" icon="o-home" link="{{ route('dashboard') }}" />
                    <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" />
                    <x-menu-separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-menu-item title="{{ __('Log Out') }}" icon="o-power" data-test="logout-button" onclick="this.closest('form').submit()" />
                    </form>
                </x-menu>
            </x-slot:sidebar>

            <x-slot:content>
                {{ $slot }}
            </x-slot:content>
        </x-main>

        {{-- TOAST area --}}
        <x-toast />
    </body>
</html>
