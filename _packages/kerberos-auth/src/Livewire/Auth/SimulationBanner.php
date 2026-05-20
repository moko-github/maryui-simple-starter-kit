<?php

namespace MokoGithub\KerberosAuth\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use MokoGithub\KerberosAuth\Services\KerberosAuthService;

class SimulationBanner extends Component
{
    public ?string $currentSimulation = null;

    public function mount(): void
    {
        $this->loadSimulation();
    }

    public function loadSimulation(): void
    {
        $kerberosService = app(KerberosAuthService::class);
        $this->currentSimulation = $kerberosService->getSimulatedKerberos();
    }

    public function disable(): void
    {
        $kerberosService = app(KerberosAuthService::class);
        $kerberosService->disableSimulation();
        $this->currentSimulation = null;

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        redirect()->route('login')
            ->with('success', 'Simulation désactivée. Veuillez vous reconnecter.');
    }

    public function getIsActiveProperty(): bool
    {
        return config('kerberos.simulation_mode', false)
            && ! app()->environment('production')
            && ! empty($this->currentSimulation);
    }

    public function render(): mixed
    {
        return view('kerberos-auth::livewire.auth.simulation-banner');
    }
}
