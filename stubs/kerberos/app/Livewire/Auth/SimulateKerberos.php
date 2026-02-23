<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\KerberosAuthService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SimulateKerberos extends Component
{
    public string $customKerberos = '';

    public ?string $selectedKerberos = null;

    public ?string $currentSimulation = null;

    public function mount(): void
    {
        $service = app(KerberosAuthService::class);
        $this->currentSimulation = $service->getSimulatedKerberos();
    }

    #[Computed]
    public function availableKerberos(): Collection
    {
        return User::whereNotNull('kerberos')
            ->orderBy('kerberos')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function simulationEnabled(): bool
    {
        return config('kerberos.simulation_mode', false) && ! app()->environment('production');
    }

    public function simulate(): void
    {
        $kerberos = ! empty($this->customKerberos) ? $this->customKerberos : $this->selectedKerberos;

        if (empty($kerberos)) {
            session()->flash('error', 'Please enter or select a Kerberos identifier.');

            return;
        }

        try {
            $service = app(KerberosAuthService::class);
            $service->enableSimulation($kerberos);

            $this->currentSimulation = $kerberos;
            $this->customKerberos = '';
            $this->selectedKerberos = null;

            $this->redirect(route('dashboard'));
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function disable(): void
    {
        $service = app(KerberosAuthService::class);
        $service->disableSimulation();

        $this->currentSimulation = null;

        session()->flash('success', 'Simulation disabled.');
    }

    public function render(): mixed
    {
        return view('livewire.auth.simulate-kerberos');
    }
}
