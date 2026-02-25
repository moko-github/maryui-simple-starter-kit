<?php

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserCreated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
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

    #[Validate('required|max:100')]
    public string $name = '';

    #[Validate('required|email|max:50|unique:users')]
    public string $email = '';

    public string $password = '';

    #[Validate('nullable|image|max:1024')]
    public mixed $avatar = null;

    #[Validate('required|int')]
    public int $status;

    #[Validate('array')]
    public array $rolesGiven = [];

    public array $statusOptions;

    public string $searchRole = '';

    public function mount(): void
    {
        $this->status = UserStatus::ACTIVE->value;
        $this->statusOptions = UserStatus::all();
    }

    private function supportsRoles(): bool
    {
        return class_exists(\App\Models\Role::class)
            && Schema::hasTable('roles');
    }

    #[Computed]
    public function rowDecoration(): array
    {
        return [];
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $data = $this->validate();

        $randomPassword = \Str::password(12);
        $data['password'] = Hash::make(value: $randomPassword);

        $this->processUpload($data);

        $user = User::create(Arr::except($data, 'rolesGiven'));

        if (!empty($this->rolesGiven)) {
            $this->authorize('assignRole', $user);

            if ($this->supportsRoles()) {
                $user->role_id = $this->rolesGiven[0] ?? null;
                $user->save();
            }
        }

        $user->notify(new UserCreated($randomPassword));

        $this->success(__("User {$user->name} created with success."), redirectTo: route('mfc-users.index'));
    }

    private function processUpload(array &$data): void
    {
        if (!$this->avatar || !($this->avatar instanceof \Illuminate\Http\UploadedFile)) {
            return;
        }

        $url = $this->avatar->store('users', 'public');
        $data['avatar'] = "/storage/{$url}";
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

    public function headersRole(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name'],
        ];
    }

    public function with(): array
    {
        return [
            'supportsRoles' => $this->supportsRoles(),
            'roles' => $this->supportsRoles() ? $this->roles() : new LengthAwarePaginator([], 0, 10),
            'headersRole' => $this->headersRole(),
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
