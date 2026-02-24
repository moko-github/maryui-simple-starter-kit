<div>
    @if ($submitted)
        <div class="flex flex-col gap-6">
            <x-auth-header
                :title="__('Demande d\'accès envoyée')"
                :description="__('Votre demande a été transmise aux administrateurs')"
            />

            <x-mary-card class="bg-success/10 border-success">
                <div class="flex flex-col gap-4 text-center">
                    <div class="flex justify-center">
                        <x-mary-icon name="o-check-circle" class="w-16 h-16 text-success" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <p class="text-base-content/90">
                            Votre demande d'accès a bien été envoyée aux administrateurs.
                        </p>
                        <p class="text-sm text-base-content/70">
                            Vous serez notifié par email dès que votre demande aura été traitée.
                        </p>
                    </div>

                    <div class="mt-4">
                        <x-mary-button
                            label="Retour à la connexion"
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
                :title="__('Demande d\'accès')"
                :description="__('Votre compte n\'a pas encore de rôle attribué. Veuillez remplir ce formulaire.')"
            />

            <x-mary-card class="bg-warning/10 border-warning">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6 text-warning flex-shrink-0 mt-1" />
                    <div class="flex flex-col gap-1">
                        <p class="font-medium text-base-content">Compte sans rôle</p>
                        <p class="text-sm text-base-content/70">
                            Votre identifiant Kerberos <strong>{{ $kerberos }}</strong> est reconnu, mais votre compte n'a aucun rôle attribué. Veuillez justifier votre demande d'accès ci-dessous.
                        </p>
                    </div>
                </div>
            </x-mary-card>

            <form wire:submit="submit" class="flex flex-col gap-6">
                <x-mary-input
                    wire:model="kerberos"
                    name="kerberos"
                    label="Identifiant Kerberos"
                    readonly
                    icon="o-identification"
                    hint="Votre identifiant Kerberos détecté automatiquement"
                />

                <x-mary-textarea
                    wire:model="justification"
                    name="justification"
                    label="Justification de votre demande"
                    placeholder="Expliquez pourquoi vous avez besoin d'accéder à l'application (minimum 20 caractères)..."
                    rows="5"
                    required
                    hint="Minimum 20 caractères, maximum 500 caractères"
                />

                <div class="text-sm text-base-content/70 bg-info/10 rounded-lg p-4 border border-info/20">
                    <div class="flex items-start gap-2">
                        <x-mary-icon name="o-information-circle" class="w-5 h-5 text-info flex-shrink-0 mt-0.5" />
                        <p>
                            Les administrateurs recevront votre demande par email et vous serez notifié une fois celle-ci traitée.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <x-mary-button
                        type="submit"
                        label="Envoyer la demande d'accès"
                        class="btn-primary w-full"
                        icon="o-paper-airplane"
                        spinner="submit"
                    />

                    <x-mary-button
                        label="Retour à la connexion"
                        link="{{ route('login') }}"
                        class="btn-ghost w-full"
                        icon="o-arrow-left"
                    />
                </div>
            </form>
        </div>
    @endif
</div>
