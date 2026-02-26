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
                @if($supportsRoles)
                    <x-mary-select :label="__('Role')" wire:model="roleId" :options="$roles" :placeholder="__('No role')"/>
                @endif

                <x-slot:actions>
                    <x-mary-button :label="__('Cancel')" :link="route('mfc-users.index')" class="btn-soft"/>
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
