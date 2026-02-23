<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class AccessDenied extends Component
{
    public string $kerberos = '';

    public function mount(): void
    {
        $this->kerberos = session('unknown_kerberos', '');

        if (empty($this->kerberos)) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function backToLogin(): void
    {
        session()->forget('unknown_kerberos');

        $this->redirect(route('login'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.access-denied');
    }
}
