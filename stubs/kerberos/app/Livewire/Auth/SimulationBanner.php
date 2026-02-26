<?php

namespace App\Livewire\Auth;

use App\Services\KerberosAuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SimulationBanner extends Component
{
    public ?string $currentSimulation = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadSimulation();
    }

    /**
     * Load current simulation status.
     */
    public function loadSimulation(): void
    {
        $kerberosService = app(KerberosAuthService::class);
        $this->currentSimulation = $kerberosService->getSimulatedKerberos();
    }

    /**
     * Disable Kerberos simulation and logout.
     */
    public function disable(): void
    {
        $kerberosService = app(KerberosAuthService::class);
        $kerberosService->disableSimulation();
        $this->currentSimulation = null;

        // Logout the user to force re-authentication
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        // Redirect to login with success message
        redirect()->route('login')
            ->with('success', 'Simulation désactivée. Veuillez vous reconnecter.');
    }

    /**
     * Check if simulation mode is enabled and active.
     */
    public function getIsActiveProperty(): bool
    {
        return config('kerberos.simulation_mode', false)
            && ! app()->environment('production')
            && ! empty($this->currentSimulation);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.auth.simulation-banner');
    }
}
