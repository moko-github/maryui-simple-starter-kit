<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use App\Enums\UserStatus;
use App\Notifications\UserCreated;

new class extends Component {
    use Toast, WithFileUploads, WithPagination;

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

        $this->success(__("User {$user->name} created with success."), redirectTo: route('users.index'));
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

}; ?>

@assets
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
@endassets

<x-pages.layout :page-title="__('Create User')">
    <x-slot:content>
        <div class="grid gap-5 lg:grid-cols-2">
            <x-mary-form wire:submit="save">
                @can('create', \App\Models\User::class)
                    <x-mary-file wire:model="avatar" accept="image/png, image/jpeg" crop-after-change>
                        <img src="/images/empty-user.jpg" class="h-36 rounded-lg"/>
                    </x-mary-file>
                @endcan

                <x-mary-input :label="__('Name')" wire:model="name"/>
                <x-mary-input :label="__('Email')" wire:model="email"/>
                @can('create', \App\Models\User::class)
                    <x-mary-group :label="__('Status')" wire:model="status" :options="$statusOptions"
                                  class="[&:checked]:!btn-primary"/>
                @endcan

                <x-slot:actions>
                    <x-mary-button :label="__('Cancel')" :link="route('users.index')" class="btn-soft"/>
                    <x-mary-button :label="__('Save')" icon="o-paper-airplane" spinner="save" type="submit"
                                   class="btn-primary"/>
                </x-slot:actions>
            </x-mary-form>
            <div class="hidden lg:block place-self-center w-full">
                @if($supportsRoles)
                    <div class="m-3">
                        <x-partials.header-title :separator="true" :heading="__('Roles')"/>
                        <x-mary-input class="input-sm" :placeholder="__('Search...')"
                                      wire:model.live.debounce="searchRole" clearable
                                      icon="o-magnifying-glass"/>
                    </div>
                    <x-mary-table
                        :headers="$headersRole"
                        :rows="$roles"
                        :row-decoration="$this->rowDecoration"
                        wire:model="rolesGiven"
                        selectable
                        with-pagination/>
                @else
                    <img src="/images/user-action-page.svg" width="300" class="mx-auto"/>
                @endif
            </div>
        </div>
    </x-slot:content>
</x-pages.layout>
