<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Profile Settings') }}</h2>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-input wire:model="name" label="{{ __('Name') }}" type="text" required autofocus autocomplete="name" inline />

            <div>
                <x-input wire:model="email" label="{{ __('Email') }}" type="email" required autocomplete="email" inline />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <p class="mt-4 text-sm text-base-content/70">
                            {{ __('Your email address is unverified.') }}

                            <a class="link link-primary text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </a>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-button label="{{ __('Save') }}" type="submit" class="btn-primary" />
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
