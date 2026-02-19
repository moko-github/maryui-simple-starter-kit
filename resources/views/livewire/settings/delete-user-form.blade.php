<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-semibold">{{ __('Delete account') }}</h3>
        <p class="text-sm text-base-content/70">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button
        label="{{ __('Delete account') }}"
        class="btn-error"
        @click="$wire.showDeleteModal = true"
    />

    <x-modal wire:model="showDeleteModal" title="{{ __('Are you sure you want to delete your account?') }}" class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <p class="text-sm text-base-content/70">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <x-password wire:model="password" label="{{ __('Password') }}" right inline />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-button label="{{ __('Cancel') }}" @click="$wire.showDeleteModal = false" />
                <x-button label="{{ __('Delete account') }}" class="btn-error" type="submit" />
            </div>
        </form>
    </x-modal>
</section>
