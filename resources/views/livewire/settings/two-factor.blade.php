<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Two-Factor Authentication Settings') }}</h2>

    <x-settings.layout
        :heading="__('Two Factor Authentication')"
        :subheading="__('Manage your two-factor authentication settings')"
    >
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
            @if ($twoFactorEnabled)
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <x-badge value="{{ __('Enabled') }}" class="badge-success" />
                    </div>

                    <p class="text-sm text-base-content/70">
                        {{ __('With two-factor authentication enabled, you will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </p>

                    <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>

                    <div class="flex justify-start">
                        <x-button
                            label="{{ __('Disable 2FA') }}"
                            icon="o-shield-exclamation"
                            class="btn-error"
                            wire:click="disable"
                        />
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <x-badge value="{{ __('Disabled') }}" class="badge-error" />
                    </div>

                    <p class="text-sm text-base-content/60">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <x-button
                        label="{{ __('Enable 2FA') }}"
                        icon="o-shield-check"
                        class="btn-primary"
                        wire:click="enable"
                    />
                </div>
            @endif
        </div>
    </x-settings.layout>

    <x-modal wire:model="showModal" title="{{ $this->modalConfig['title'] }}" class="max-w-md" @close="closeModal">
        <div class="space-y-6">
            <p class="text-sm text-base-content/70 text-center">{{ $this->modalConfig['description'] }}</p>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-3 justify-center">
                        <input
                            name="code"
                            wire:model="code"
                            type="text"
                            maxlength="6"
                            placeholder="000000"
                            class="input input-bordered text-center text-2xl tracking-[0.5em] w-52"
                            autocomplete="one-time-code"
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <x-button
                            label="{{ __('Back') }}"
                            class="btn-outline flex-1"
                            wire:click="resetVerification"
                        />

                        <x-button
                            label="{{ __('Confirm') }}"
                            class="btn-primary flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                        />
                    </div>
                </div>
            @else
                @error('setupData')
                    <x-alert title="{{ $message }}" icon="o-x-circle" class="alert-error" />
                @enderror

                <div class="flex justify-center">
                    <div class="relative w-64 overflow-hidden border rounded-lg border-base-300 aspect-square">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex items-center justify-center bg-base-100 animate-pulse">
                                <x-loading class="loading-spinner" />
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-4 bg-white">
                                {!! $qrCodeSvg !!}
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <x-button
                        label="{{ $this->modalConfig['buttonText'] }}"
                        :disabled="$errors->has('setupData')"
                        class="btn-primary w-full"
                        wire:click="showVerificationIfNecessary"
                    />
                </div>

                <div class="space-y-4">
                    <div class="divider text-sm">{{ __('or, enter the code manually') }}</div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex items-stretch w-full border rounded-xl border-base-300">
                            @empty($manualSetupKey)
                                <div class="flex items-center justify-center w-full p-3 bg-base-200">
                                    <x-loading class="loading-spinner loading-sm" />
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full p-3 bg-transparent outline-none"
                                />

                                <button
                                    @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-base-300"
                                >
                                    <x-icon name="o-document-duplicate" x-show="!copied" class="w-5 h-5" />
                                    <x-icon name="o-check" x-show="copied" class="w-5 h-5 text-green-500" />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-modal>
</section>
