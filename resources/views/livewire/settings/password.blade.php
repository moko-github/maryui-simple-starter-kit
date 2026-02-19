<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Password Settings') }}</h2>

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <x-password
                wire:model="current_password"
                label="{{ __('Current password') }}"
                required
                autocomplete="current-password"
                right
                inline
            />
            <x-password
                wire:model="password"
                label="{{ __('New password') }}"
                required
                autocomplete="new-password"
                right
                inline
            />
            <x-password
                wire:model="password_confirmation"
                label="{{ __('Confirm Password') }}"
                required
                autocomplete="new-password"
                right
                inline
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-button label="{{ __('Save') }}" type="submit" class="btn-primary" />
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
