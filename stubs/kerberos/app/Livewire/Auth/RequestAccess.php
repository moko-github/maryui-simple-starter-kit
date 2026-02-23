<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\KerberosAuthService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class RequestAccess extends Component
{
    public string $kerberos = '';

    public ?int $user_id = null;

    public string $justification = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->kerberos = session('pending_kerberos', '');
        $this->user_id = session('pending_user_id');

        if (empty($this->kerberos)) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    protected function rules(): array
    {
        return [
            'justification' => ['required', 'string', 'min:20', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'justification.required' => 'You must provide a justification for your access request.',
            'justification.min' => 'The justification must be at least 20 characters.',
            'justification.max' => 'The justification cannot exceed 500 characters.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $user = User::find($this->user_id);

        if (! $user) {
            session()->flash('error', 'An error occurred. Please try again.');

            return;
        }

        $kerberosService = app(KerberosAuthService::class);
        $kerberosService->createAccessRequest($user, $this->kerberos, $this->justification);

        session()->forget(['pending_kerberos', 'pending_user_id']);

        $this->submitted = true;
    }

    public function render(): mixed
    {
        return view('livewire.auth.request-access');
    }
}
