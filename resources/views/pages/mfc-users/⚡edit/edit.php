<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use WithFileUploads;

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
                Rule::unique(User::class)->ignore($this->user->id),
            ],
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

        $this->success(__('User updated with success.'), redirectTo: route('mfc-users.index'));
    }

    private function processUpload(array &$validated): void
    {
        if (!$this->avatar || !($this->avatar instanceof \Illuminate\Http\UploadedFile)) {
            return;
        }

        $this->validate([
            'avatar' => 'image|max:1024',
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
};
