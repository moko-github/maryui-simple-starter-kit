<div>
    @if ($this->simulationEnabled)
        <div class="mb-6">
            <x-mary-card class="bg-warning/10 border-warning border-2">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-base-content">⚠️ Mode Développement</h3>
                            <p class="text-sm text-base-content/70">Simulation Kerberos active (environnement {{ app()->environment() }})</p>
                        </div>
                    </div>

                    @if ($currentSimulation)
                        <div class="bg-success/10 border border-success/20 rounded-lg p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 flex-1">
                                    <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success flex-shrink-0" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-base-content">Simulation en cours</p>
                                        <p class="text-xs font-mono text-base-content/70 break-all">{{ $currentSimulation }}</p>
                                    </div>
                                </div>
                                <x-mary-button
                                    wire:click="disable"
                                    label="Désactiver"
                                    class="btn-sm btn-error"
                                    icon="o-x-mark"
                                    spinner="disable"
                                />
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            <div class="divider my-0 text-xs text-base-content/50">Activer la simulation</div>

                            <x-mary-input
                                wire:model="customKerberos"
                                name="customKerberos"
                                label="Identifiant Kerberos personnalisé"
                                placeholder="prenom.nom@exemple.fr"
                                icon="o-pencil"
                                hint="Saisissez n'importe quel identifiant Kerberos"
                            />

                            <div class="text-center text-xs text-base-content/50">ou</div>

                            <x-mary-select
                                wire:model="selectedKerberos"
                                name="selectedKerberos"
                                label="Sélectionner un utilisateur existant"
                                :options="$this->availableKerberos"
                                option-label="kerberos"
                                option-value="kerberos"
                                placeholder="Choisir un identifiant existant..."
                                icon="o-users"
                                hint="Les 10 premiers identifiants de la base de données"
                            />

                            <x-mary-button
                                wire:click="simulate"
                                label="Simuler la connexion"
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
                                <strong>Attention :</strong> Ce mode de simulation est <strong>strictement réservé aux environnements de développement et de pré-production</strong>. Il est automatiquement désactivé en production.
                            </p>
                        </div>
                    </div>
                </div>
            </x-mary-card>
        </div>
    @endif
</div>
