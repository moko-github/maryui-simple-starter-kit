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
                    <x-mary-button :label="__('Cancel')" :link="route('mfc-users.index')" class="btn-soft"/>
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
