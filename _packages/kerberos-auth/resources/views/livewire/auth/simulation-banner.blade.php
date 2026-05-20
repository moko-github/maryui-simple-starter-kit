<div>
    @if ($this->isActive)
        <div class="p-3 bg-warning/20 border-b border-warning/30">
            <div class="flex items-center justify-between gap-3">
                <!-- Info simulation -->
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-warning flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-base-content">Mode Simulation</p>
                        <p class="text-xs font-mono text-base-content/70 truncate" title="{{ $currentSimulation }}">
                            {{ $currentSimulation }}
                        </p>
                    </div>
                </div>
                <!-- Bouton désactiver -->
                <button wire:click="disable" wire:loading.attr="disabled" class="btn btn-xs btn-error flex-shrink-0"
                    title="Désactiver la simulation">
                    <x-mary-icon name="o-x-mark" class="w-3 h-3" />
                    <span wire:loading.remove wire:target="disable">Quitter</span>
                    <span wire:loading wire:target="disable">
                        <span class="loading loading-spinner loading-xs"></span>
                    </span>
                </button>
            </div>
            <!-- Warning message -->
            <div class="mt-2 flex items-start gap-1.5">
                <x-mary-icon name="o-information-circle" class="w-3.5 h-3.5 text-warning/70 flex-shrink-0 mt-0.5" />
                <p class="text-[10px] text-base-content/60 leading-tight">
                    Vous êtes connecté en mode simulation ({{ app()->environment() }}). Cliquez sur "Quitter" pour vous
                    déconnecter et désactiver la simulation.
                </p>
            </div>
        </div>
    @endif
</div>
