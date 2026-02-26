<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use App\Enums\UserStatus;

new class extends Component {
    use Toast, WithFileUploads;

    public User $user;

    #[Validate('required|max:255')]
    public string $name = '';

    public string $email = '';

    #[Validate('nullable')]
    public mixed $avatar = null;

    #[Validate('required|int')]
    public int $status;

    #[Validate('nullable|integer')]
    public ?int $roleId = null;

    public array $statusOptions;

    public function mount(): void
    {
        if (auth()->id() === $this->user->id) {
            $this->redirectRoute('settings.profile');
        }

        $this->fill($this->user);

        $this->roleId = $this->user->role_id;

        $this->statusOptions = UserStatus::all();
    }

    private function supportsRoles(): bool
    {
        return class_exists(\App\Models\Role::class)
            && Schema::hasTable('roles');
    }

    protected function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id)
            ]
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate();

        $this->processUpload($validated);

        $this->user->update(Arr::except($validated, ['roleId']));

        if ($this->supportsRoles() && auth()->user()->can('assignRole', $this->user)) {
            $this->user->role_id = $this->roleId;
            $this->user->save();
        }

        $this->success(__('User updated with success.'), redirectTo: route('users.index'));
    }

    private function processUpload(array &$validated): void
    {
        if (!$this->avatar || !($this->avatar instanceof \Illuminate\Http\UploadedFile)) {
            return;
        }

        $this->validate([
            'avatar' => 'image|max:1024'
        ]);

        if ($this->user->avatar) {
            $path = str($this->user->avatar)->after('/storage/');
            \Storage::disk('public')->delete($path);
        }

        $url = $this->avatar->store('users', 'public');
        $validated['avatar'] = "/storage/{$url}";
    }

    public function with(): array
    {
        return [
            'supportsRoles' => $this->supportsRoles(),
            'roles' => $this->supportsRoles() ? \App\Models\Role::all() : collect(),
        ];
    }

    public function exception(Throwable $e, $stopPropagation): void
    {
        if ($e instanceof AuthorizationException) {
            $this->error($e->getMessage());

            $stopPropagation();
        }
    }

}; ?>

@assets
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
@endassets

<x-pages.layout :page-title="__('Update') . ' - ' . $user->name">
    <x-slot:content>
        <div class="grid gap-5 lg:grid-cols-2">
            <x-mary-form wire:submit="save">
                @can('update', $user)
                    <div class="indicator">
                    <span @class([
                        'indicator-item status',
                        'status-success' => $user->status === UserStatus::ACTIVE,
                        'status-warning' => $user->status === UserStatus::INACTIVE,
                        'status-error' => $user->status === UserStatus::SUSPENDED,
                    ])></span>
                        <x-mary-file wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                            <img src="{{ $user->avatar ?? '/images/empty-user.jpg' }}" class="h-36 rounded-lg"/>
                        </x-mary-file>
                    </div>
                @endcan

                <x-mary-input :label="__('Name')" wire:model="name"/>
                <x-mary-input :disabled="auth()->user()->cannot('manageStatus', $user)" :label="__('Email')" wire:model="email"/>
                @can('manageStatus', $user)
                    <x-mary-group :disabled="auth()->user()->cannot('manageStatus', $user)" :label="__('Status')" wire:model="status" :options="$statusOptions"
                                  class="[&:checked]:!btn-primary"/>
                @endcan
                @if($supportsRoles)
                    @can('assignRole', $user)
                        <x-mary-select :label="__('Role')" wire:model="roleId" :options="$roles" :placeholder="__('No role')"/>
                    @endcan
                @endif

                <x-slot:actions>
                    <x-mary-button :label="__('Cancel')" :link="route('users.index')" class="btn-soft"/>
                    <x-mary-button :label="__('Save')" icon="o-paper-airplane" spinner="save" type="submit"
                                   class="btn-primary"/>
                </x-slot:actions>
            </x-mary-form>
            <div class="hidden lg:block place-self-center w-full">
                <img src="/images/user-action-page.svg" width="300" class="mx-auto"/>
            </div>
        </div>
    </x-slot:content>
</x-pages.layout>
