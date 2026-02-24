<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')
        // ->can('dashboard.view')
        ->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    // ->can('profile.view')

    Route::livewire('settings/profile', 'pages::settings.profile')
        // ->can('profile.view')
        ->name('settings.profile');
    Route::livewire('settings/password', 'pages::settings.password')
        // ->can('profile.password')
        ->name('settings.password');
    Route::prefix('users')->name('users.')->group(function () {
        Route::livewire('/', 'pages::users.index')
            // ->can('viewAny', User::class)
            ->name('index');
        Route::livewire('/index2', 'pages::users.index2')
            // ->can('viewAny', User::class)
            ->name('index2');
        Route::livewire('/create', 'pages::users.create')
            // ->can('create', User::class)
            ->name('create');
        Route::livewire('/{user}/edit', 'pages::users.edit')
            // ->can('view', 'user')
            ->name('edit');
    });

    Route::prefix('mfc-users')->name('mfc-users.')->group(function () {
        Route::livewire('/', 'pages::mfc-users.index')->name('index');
        Route::livewire('/create', 'pages::mfc-users.create')->name('create');
        Route::livewire('/{user}/edit', 'pages::mfc-users.edit')->name('edit');
    });
});

require __DIR__.'/auth.php';
