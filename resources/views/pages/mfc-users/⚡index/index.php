<?php

use App\Models\User;
use App\Enums\UserStatus;
use App\Traits\ClearsFilters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use WithPagination;
    use ClearsFilters;

    public string $search = '';

    public ?int $status = null;

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public bool $modal = false;

    public mixed $targetDelete = null;

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'avatar', 'label' => 'Avatar', 'sortable' => false, 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email', 'sortable' => false],
        ];
    }

    public function delete(User $user): void
    {
        $this->authorize('delete', $user);

        if (auth()->id() === $user->id) {
            $this->redirectRoute('settings.profile');

            return;
        }

        if ($user->avatar) {
            $path = str($user->avatar)->after('/storage/');
            \Storage::disk('public')->delete($path);
        }

        $user->delete();

        $this->modal = false;

        $this->success(__("User {$user->name} has been deleted."));
    }

    public function edit(User $user): void
    {
        $this->redirectRoute('mfc-users.edit', ['user' => $user], false, true);
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->when($this->status, fn(Builder $q) => $q->where('status', $this->status))
            ->with('role')
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
            'statusGroup' => UserStatus::all(),
        ];
    }

    public function exception(Throwable $e, $stopPropagation): void
    {
        if ($e instanceof AuthorizationException) {
            $this->modal = false;
            $this->drawer = false;

            $this->error($e->getMessage());

            $stopPropagation();
        }
    }
};
