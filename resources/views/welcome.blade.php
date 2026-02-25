<x-layouts::guest>
    
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
                        <x-mary-button label="Dashboard" link="{{ url('/dashboard') }}" class="btn-primary" icon="o-home" />
                    @else
                        @if (Route::has('login'))
                            <x-mary-button label="Log in" link="{{ route('login') }}" class="btn-primary" icon="o-arrow-right-on-rectangle" />
                        @endif
                        @if (Route::has('register'))
                            <x-mary-button label="Register" link="{{ route('register') }}" class="btn-outline" icon="o-user-plus" />
                        @endif
                    @endauth
                </div>

                {{-- Resource cards --}}
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 text-left">
                    <x-mary-card title="Documentation" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Laravel has wonderful documentation covering every aspect of the framework.') }}</p>
                        <x-slot:figure>
                            <div class="bg-primary/10 p-6 flex justify-center">
                                <x-mary-icon name="o-book-open" class="w-10 h-10 text-primary" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-mary-button label="Read docs" link="https://laravel.com/docs" external class="btn-sm btn-primary" />
                        </x-slot:actions>
                    </x-mary-card>

                    <x-mary-card title="Laracasts" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Watch thousands of video tutorials on Laravel, PHP, and JavaScript.') }}</p>
                        <x-slot:figure>
                            <div class="bg-secondary/10 p-6 flex justify-center">
                                <x-mary-icon name="o-play-circle" class="w-10 h-10 text-secondary" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-mary-button label="Watch now" link="https://laracasts.com" external class="btn-sm btn-secondary" />
                        </x-slot:actions>
                    </x-mary-card>

                    <x-mary-card title="MaryUI" class="bg-base-100" shadow>
                        <p class="text-sm text-base-content/70">{{ __('Beautiful UI components for Laravel built with Livewire 3, DaisyUI and Tailwind.') }}</p>
                        <x-slot:figure>
                            <div class="bg-accent/10 p-6 flex justify-center">
                                <x-mary-icon name="o-sparkles" class="w-10 h-10 text-accent" />
                            </div>
                        </x-slot:figure>
                        <x-slot:actions>
                            <x-mary-button label="Explore" link="https://mary-ui.com" external class="btn-sm btn-accent" />
                        </x-slot:actions>
                    </x-mary-card>
                </div>
            </div>
        </div>
    </div>
</x-layouts::guest>
