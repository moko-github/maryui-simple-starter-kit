<div>
    @if ($this->simulationEnabled)
        <div class="mb-6">
            <x-mary-card class="bg-warning/10 border-warning border-2">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-base-content">⚠️ Development Mode</h3>
                            <p class="text-sm text-base-content/70">Kerberos simulation active ({{ app()->environment() }} environment)</p>
                        </div>
                    </div>

                    @if ($currentSimulation)
                        <div class="bg-success/10 border border-success/20 rounded-lg p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 flex-1">
                                    <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success flex-shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-base-content">Simulation active</p>
                                        <p class="text-xs font-mono text-base-content/70 break-all">{{ $currentSimulation }}</p>
                                    </div>
                                </div>
                                <x-mary-button
                                    wire:click="disable"
                                    label="Disable"
                                    class="btn-sm btn-error"
                                    icon="o-x-mark"
                                    spinner="disable"
                                />
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            <div class="divider my-0 text-xs text-base-content/50">Enable simulation</div>

                            <x-mary-input
                                wire:model="customKerberos"
                                name="customKerberos"
                                label="Custom Kerberos identifier"
                                placeholder="firstname.lastname@example.com"
                                icon="o-pencil"
                                hint="Enter any Kerberos identifier"
                            />

                            <div class="text-center text-xs text-base-content/50">or</div>

                            <x-mary-select
                                wire:model="selectedKerberos"
                                name="selectedKerberos"
                                label="Select an existing user"
                                :options="$this->availableKerberos"
                                option-label="kerberos"
                                option-value="kerberos"
                                placeholder="Choose an existing Kerberos..."
                                icon="o-users"
                                hint="First 10 identifiers from the database"
                            />

                            <x-mary-button
                                wire:click="simulate"
                                label="Simulate login"
                                class="btn-warning w-full"
                                icon="o-play"
                                spinner="simulate"
                            />
                        </div>
                    @endif

                    <div class="bg-error/10 border border-error/20 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <x-mary-icon name="o-shield-exclamation" class="w-5 h-5 text-error flex-shrink-0 mt-0.5" />
                            <p class="text-xs text-base-content/70">
                                <strong>Warning:</strong> This simulation mode is <strong>strictly reserved for development and staging environments</strong>. It is automatically disabled in production.
                            </p>
                        </div>
                    </div>
                </div>
            </x-mary-card>
        </div>
    @endif
</div>
