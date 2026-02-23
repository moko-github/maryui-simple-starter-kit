<div>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Accès refusé')"
            :description="__('Votre identifiant Kerberos n\'est pas reconnu')"
        />

        <x-mary-card class="bg-error/10 border-error">
            <div class="flex flex-col gap-6">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-shield-exclamation" class="w-8 h-8 text-error flex-shrink-0 mt-1" />
                    <div class="flex flex-col gap-3">
                        <div>
                            <p class="font-semibold text-lg text-base-content">Identifiant non reconnu</p>
                            <p class="text-sm text-base-content/70 mt-1">
                                L'identifiant Kerberos suivant n'est pas enregistré dans notre système :
                            </p>
                        </div>

                        <div class="bg-base-200 rounded-lg p-4 border border-base-300">
                            <p class="font-mono text-sm text-error font-medium">{{ $kerberos }}</p>
                        </div>

                        <div class="bg-info/10 rounded-lg p-4 border border-info/20">
                            <div class="flex items-start gap-2">
                                <x-mary-icon name="o-information-circle" class="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
                                <p class="text-sm text-base-content/70">
                                    Les administrateurs ont été automatiquement notifiés de cette tentative de connexion. Si vous pensez qu'il s'agit d'une erreur, veuillez contacter votre service informatique.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider my-2"></div>

                <div class="flex flex-col gap-2">
                    <p class="text-sm font-medium text-base-content">Que faire ?</p>
                    <ul class="list-disc list-inside text-sm text-base-content/70 space-y-1 ml-2">
                        <li>Assurez-vous d'être connecté au réseau de l'entreprise</li>
                        <li>Contactez votre service informatique pour vérifier votre compte</li>
                        <li>Utilisez le formulaire de connexion classique si vous disposez d'un compte local</li>
                    </ul>
                </div>
            </div>
        </x-mary-card>

        <div class="flex flex-col gap-3">
            <x-mary-button
                wire:click="backToLogin"
                label="Retour à la page de connexion"
                class="btn-primary w-full"
                icon="o-arrow-left"
            />

            <div class="text-center">
                <p class="text-xs text-base-content/50">
                    Tentative le {{ now()->format('d/m/Y à H:i:s') }}
                </p>
            </div>
        </div>
    </div>
</div>
