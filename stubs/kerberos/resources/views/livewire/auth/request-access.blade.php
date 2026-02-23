<div>
    @if ($submitted)
        <div class="flex flex-col gap-6">
            <x-auth-header
                :title="__('Access request sent')"
                :description="__('Your request has been forwarded to the administrators')"
            />

            <x-mary-card class="bg-success/10 border-success">
                <div class="flex flex-col gap-4 text-center">
                    <div class="flex justify-center">
                        <x-mary-icon name="o-check-circle" class="w-16 h-16 text-success" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <p class="text-base-content/90">
                            Your access request has been successfully sent to the administrators.
                        </p>
                        <p class="text-sm text-base-content/70">
                            You will be notified by email once your request has been processed.
                        </p>
                    </div>

                    <div class="mt-4">
                        <x-mary-button
                            label="Back to login"
                            link="{{ route('login') }}"
                            class="btn-primary"
                            icon="o-arrow-left"
                        />
                    </div>
                </div>
            </x-mary-card>
        </div>
    @else
        <div class="flex flex-col gap-6">
            <x-auth-header
                :title="__('Access request')"
                :description="__('Your account does not have a role assigned yet. Please fill in this form.')"
            />

            <x-mary-card class="bg-warning/10 border-warning">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0 mt-1" />
                    <div class="flex flex-col gap-1">
                        <p class="font-medium text-base-content">Account without role</p>
                        <p class="text-sm text-base-content/70">
                            Your Kerberos identifier <strong>{{ $kerberos }}</strong> is recognized, but your account has no role assigned. Please justify your access request below.
                        </p>
                    </div>
                </div>
            </x-mary-card>

            <form wire:submit="submit" class="flex flex-col gap-6">
                <x-mary-input
                    wire:model="kerberos"
                    name="kerberos"
                    label="Kerberos identifier"
                    readonly
                    icon="o-identification"
                    hint="Your Kerberos identifier detected automatically"
                />

                <x-mary-textarea
                    wire:model="justification"
                    name="justification"
                    label="Justification for your request"
                    placeholder="Explain why you need access to the application (minimum 20 characters)..."
                    rows="5"
                    required
                    hint="Minimum 20 characters, maximum 500 characters"
                />

                <div class="text-sm text-base-content/70 bg-info/10 rounded-lg p-4 border border-info/20">
                    <div class="flex items-start gap-2">
                        <x-mary-icon name="o-information-circle" class="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
                        <p>
                            Administrators will receive your request by email and you will be notified once it is processed.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <x-mary-button
                        type="submit"
                        label="Send access request"
                        class="btn-primary w-full"
                        icon="o-paper-airplane"
                        spinner="submit"
                    />

                    <x-mary-button
                        label="Back to login"
                        link="{{ route('login') }}"
                        class="btn-ghost w-full"
                        icon="o-arrow-left"
                    />
                </div>
            </form>
        </div>
    @endif
</div>
