<div>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Access denied')"
            :description="__('Your Kerberos identifier is not recognized')"
        />

        <x-mary-card class="bg-error/10 border-error">
            <div class="flex flex-col gap-6">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-shield-exclamation" class="w-8 h-8 text-error flex-shrink-0 mt-1" />
                    <div class="flex flex-col gap-3">
                        <div>
                            <p class="font-semibold text-lg text-base-content">Identifier not recognized</p>
                            <p class="text-sm text-base-content/70 mt-1">
                                The following Kerberos identifier is not registered in our system:
                            </p>
                        </div>

                        <div class="bg-base-200 rounded-lg p-4 border border-base-300">
                            <p class="font-mono text-sm text-error font-medium">{{ $kerberos }}</p>
                        </div>

                        <div class="bg-info/10 rounded-lg p-4 border border-info/20">
                            <div class="flex items-start gap-2">
                                <x-mary-icon name="o-information-circle" class="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
                                <p class="text-sm text-base-content/70">
                                    Administrators have been automatically notified of this login attempt. If you think this is an error, please contact your IT department.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider my-2"></div>

                <div class="flex flex-col gap-2">
                    <p class="text-sm font-medium text-base-content">What to do next?</p>
                    <ul class="list-disc list-inside text-sm text-base-content/70 space-y-1 ml-2">
                        <li>Make sure you are connected to the corporate network</li>
                        <li>Contact your IT department to verify your account</li>
                        <li>Use the classic login form if you have a local account</li>
                    </ul>
                </div>
            </div>
        </x-mary-card>

        <div class="flex flex-col gap-3">
            <x-mary-button
                wire:click="backToLogin"
                label="Back to login page"
                class="btn-primary w-full"
                icon="o-arrow-left"
            />

            <div class="text-center">
                <p class="text-xs text-base-content/50">
                    Attempt time: {{ now()->format('d/m/Y at H:i:s') }}
                </p>
            </div>
        </div>
    </div>
</div>
