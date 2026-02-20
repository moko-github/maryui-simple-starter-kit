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

});

require __DIR__.'/auth.php';
