<x-pages.layout :page-title="__('Users')">
    @can('viewAny', \App\Models\User::class)
        <x-slot:search>
            <x-mary-input class="input-sm" :placeholder="__('Search...')" wire:model.live.debounce="search" clearable
                          icon="o-magnifying-glass"/>
        </x-slot:search>
    @endcan
    <x-slot:actions>
        @can('viewAny', \App\Models\User::class)
            <x-mary-button class="btn-soft btn-sm" :label="__('Filters')" @click="$wire.drawer=true" responsive
                           icon="o-funnel"/>
        @endcan
        @can('create', \App\Models\User::class)
            <x-mary-button :link="route('mfc-users.create')" icon="o-plus" :label="__('Create')" class="btn-primary btn-sm"
                           responsive/>
        @endcan
    </x-slot:actions>

    <x-slot:content>
        <x-mary-table :headers="$headers" :rows="$users" :sort-by="$sortBy" with-pagination>
            @scope('cell_avatar', $user)
            <div class="indicator tooltip" data-tip="{{ $user->status->label() }}">
                <span @class([
                    'indicator-item status',
                    'status-success' => $user->status === \App\Enums\UserStatus::ACTIVE,
                    'status-warning' => $user->status === \App\Enums\UserStatus::INACTIVE,
                    'status-error' => $user->status === \App\Enums\UserStatus::SUSPENDED,
                ])></span>
                <x-mary-avatar image="{{ $user?->avatar ?? '/images/empty-user.jpg' }}" class="!w-8 !rounded-lg"/>
            </div>
            @endscope

            @scope('actions', $user)
            <div class="inline-flex gap-2 items-center justify-end">
                @if($supportsRoles && $user->role)
                    <x-mary-badge :value="$user->role->name" class="badge-secondary badge-xs" />
                @endif
                @can('view', $user)
                    <x-mary-dropdown>
                        <x-slot:trigger>
                            <x-mary-button icon="o-ellipsis-horizontal" class="btn-circle"/>
                        </x-slot:trigger>

                        @can('update', $user)
                            <x-mary-menu-item :title="__('Edit')" icon="o-pencil"
                                              :link="route('mfc-users.edit', ['user' => $user->id])"/>
                        @endcan
                        @can('delete', $user)
                            <x-mary-menu-item :title="__('Delete')" icon="o-trash" class="text-error"
                                              @click="$dispatch('target-delete', { user: {{ $user->id }} })" spinner/>
                        @endcan
                    </x-mary-dropdown>
                @endcan
            </div>
            @endscope
        </x-mary-table>
    </x-slot:content>

    @can('viewAny', \App\Models\User::class)
        <x-mary-drawer wire:model="drawer" :title="__('Filters')" right separator with-close-button class="lg:w-1/3">
            <x-mary-group :label="__('Status')" wire:model.live="status" :options="$statusGroup"
                          class="[&:checked]:!btn-primary"/>

            <x-slot:actions>
                <x-mary-button :label="__('Reset')" icon="o-x-mark" wire:click="clear" spinner class="btn-soft"/>
                <x-mary-button :label="__('Done')" icon="o-check" class="btn-primary" @click="$wire.drawer = false"/>
            </x-slot:actions>
        </x-mary-drawer>
    @endcan

    <x-mary-modal wire:model="modal" :title="__('Delete')" :subtitle="__('Are you sure?')" class="backdrop-blur">
        <x-slot:actions>
            <x-mary-button :label="__('Yes')" class="btn-error" wire:click="delete($wire.targetDelete)"
                           spinner="delete"/>
            <x-mary-button :label="__('Cancel')" class="btn-soft" @click="$wire.modal = false"/>
        </x-slot:actions>
    </x-mary-modal>
</x-pages.layout>

@script
<script>
    $wire.on('target-delete', (event) => {
        $wire.modal = true;
        $wire.targetDelete = event.user;
    });
</script>
@endscript
