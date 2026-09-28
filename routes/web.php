<?php

use App\Livewire\Locations;
use App\Livewire\Teachers;
use App\Livewire\Users;
use App\Models\City;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'inicio')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('inicio', 'dashboard')->name('dashboard');

    Route::livewire('docentes', Teachers\Index::class)
        ->name('teachers.index')
        ->can('viewAny', Teacher::class);

    Route::livewire('lugares', Locations\Index::class)
        ->name('locations.index')
        ->can('viewAny', City::class);

    Route::livewire('usuarios', Users\Index::class)
        ->name('users.index')
        ->can('viewAny', User::class);
});

require __DIR__.'/settings.php';
