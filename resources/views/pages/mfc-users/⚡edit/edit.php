<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use WithFileUploads;
    use WithPagination;

    public User $user;

    #[Validate('required|max:255')]
    public string $name = '';

    public string $email = '';

    #[Validate('nullable')]
    public mixed $avatar = null;

    #[Validate('required|int')]
    public int $status;

    #[Validate('array')]
    public array $rolesGiven = [];

    #[Validate('array')]
    public array $permissionsGiven = [];

    public string $searchRole = '';

    public string $searchPermission = '';

    public array $statusOptions;

    public function mount(): void
    {
        if (auth()->id() === $this->user->id) {
            $this->redirectRoute('settings.profile');
        }

        $this->fill($this->user);

        if ($this->supportsRoles()) {
            $this->rolesGiven = $this->user->role_id ? [$this->user->role_id] : [];
        }

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

        $this->user->update(Arr::except($validated, ['rolesGiven', 'permissionsGiven']));

        if ($this->supportsRoles() && auth()->user()->can('assignRole', $this->user)) {
            $this->user->role_id = $this->rolesGiven[0] ?? null;
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

    #[Computed]
    public function rowDecoration(): array
    {
        return [];
    }

    public function roles(): LengthAwarePaginator
    {
        if (!$this->supportsRoles()) {
            return new LengthAwarePaginator([], 0, 10);
        }

        return \App\Models\Role::query()
            ->when($this->searchRole, fn(Builder $q) => $q->where('name', 'like', "%$this->searchRole%"))
            ->paginate(10);
    }

    public function permissions(): LengthAwarePaginator
    {
        if (!$this->supportsRoles()) {
            return new LengthAwarePaginator([], 0, 10);
        }

        return \Spatie\Permission\Models\Permission::query()
            ->when($this->searchPermission, fn(Builder $q) => $q->where('name', 'like', "%$this->searchPermission%"))
            ->paginate(10);
    }

    public function headersRole(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name'],
        ];
    }

    public function headersPermission(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name'],
        ];
    }

    public function with(): array
    {
        $data = [
            'supportsRoles' => $this->supportsRoles(),
            'roles' => $this->supportsRoles() ? $this->roles() : new LengthAwarePaginator([], 0, 10),
            'headersRole' => $this->headersRole(),
        ];

        if ($this->supportsRoles() && auth()->user()->can('managePermissions', $this->user)) {
            $data = array_merge($data, [
                'permissions' => $this->permissions(),
                'headersPermission' => $this->headersPermission(),
            ]);
        }

        return $data;
    }

    public function exception(Throwable $e, $stopPropagation): void
    {
        if ($e instanceof AuthorizationException) {
            $this->error($e->getMessage());

            $stopPropagation();
        }
    }
};
