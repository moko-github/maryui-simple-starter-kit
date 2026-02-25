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
                        'status-success' => $user->status === \App\Enums\UserStatus::ACTIVE,
                        'status-warning' => $user->status === \App\Enums\UserStatus::INACTIVE,
                        'status-error' => $user->status === \App\Enums\UserStatus::SUSPENDED,
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

                <x-slot:actions>
                    <x-mary-button :label="__('Cancel')" :link="route('mfc-users.index')" class="btn-soft"/>
                    <x-mary-button :label="__('Save')" icon="o-paper-airplane" spinner="save" type="submit"
                                   class="btn-primary"/>
                </x-slot:actions>
            </x-mary-form>
            <div class="hidden lg:block place-self-center w-full">
                @if($supportsRoles && !auth()->user()->can('managePermissions', $user))
                    @can('assignRole', $user)
                        <div class="m-3">
                            <x-partials.header-title :separator="true" :heading="__('Roles')"/>
                            @can('assignRole', $user)
                                <x-mary-input class="input-sm" :placeholder="__('Search...')"
                                              wire:model.live.debounce="searchRole" clearable
                                              icon="o-magnifying-glass"/>
                            @endcan
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
                    @endcan
                @else
                    <img src="/images/user-action-page.svg" width="300" class="mx-auto"/>
                @endif
            </div>
        </div>
        @if($supportsRoles && auth()->user()->can('managePermissions', $user))
        <div class="flex gap-5 w-full">
            <div class="w-full lg:w-1/2">
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
            </div>
            <div class="w-full lg:w-1/2">
                <div class="m-3">
                    <x-partials.header-title :separator="true" :heading="__('Permissions')"/>
                    <x-mary-input class="input-sm" :placeholder="__('Search...')"
                                  wire:model.live.debounce="searchPermission" clearable
                                  icon="o-magnifying-glass"/>
                </div>
                <x-mary-table
                    :headers="$headersPermission"
                    :rows="$permissions"
                    wire:model="permissionsGiven"
                    selectable
                    with-pagination/>
            </div>
        </div>
        @endif
    </x-slot:content>
</x-pages.layout>
