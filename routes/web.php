<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\Assemblies;
use App\Livewire\Desk;
use App\Livewire\Locations;
use App\Livewire\Teachers;
use App\Livewire\Users;
use App\Models\Assembly;
use App\Models\City;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'inicio')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inicio', DashboardController::class)->name('dashboard');

    Route::livewire('docentes', Teachers\Index::class)
        ->name('teachers.index')
        ->can('viewAny', Teacher::class);

    Route::livewire('lugares', Locations\Index::class)
        ->name('locations.index')
        ->can('viewAny', City::class);

    Route::livewire('jornadas', Assemblies\Index::class)
        ->name('assemblies.index')
        ->can('viewAny', Assembly::class);

    Route::livewire('usuarios', Users\Index::class)
        ->name('users.index')
        ->can('viewAny', User::class);

    Route::livewire('registro', Desk\Index::class)
        ->name('desk')
        ->can('useDesk', Assembly::class);
});

require __DIR__.'/settings.php';
