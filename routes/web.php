<?php

use App\Livewire\Users;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'inicio')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('inicio', 'dashboard')->name('dashboard');

    Route::livewire('usuarios', Users\Index::class)
        ->name('users.index')
        ->can('viewAny', User::class);
});

require __DIR__.'/settings.php';
