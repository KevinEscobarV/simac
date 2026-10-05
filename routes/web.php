<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveScreenController;
use App\Http\Controllers\PrintCardsController;
use App\Http\Controllers\RaffleRecordController;
use App\Livewire\Assemblies;
use App\Livewire\Configuration;
use App\Livewire\Desk;
use App\Livewire\Locations;
use App\Livewire\Raffles;
use App\Livewire\Screen;
use App\Livewire\Teachers;
use App\Livewire\Users;
use App\Models\Assembly;
use App\Models\City;
use App\Models\Projection;
use App\Models\Raffle;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'inicio')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inicio', DashboardController::class)->name('dashboard');

    Route::livewire('docentes', Teachers\Index::class)
        ->name('teachers.index')
        ->can('viewAny', Teacher::class);

    Route::livewire('docentes/carnes', Teachers\Cards::class)
        ->name('teachers.cards')
        ->can('viewAny', Teacher::class);

    Route::get('docentes/carnes/imprimir', PrintCardsController::class)
        ->name('teachers.cards.print')
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

    Route::livewire('configuracion', Configuration\Index::class)
        ->name('configuration')
        ->can('manage', Setting::class);

    Route::livewire('sorteos', Raffles\Index::class)
        ->name('raffles.index')
        ->can('viewAny', Raffle::class);

    Route::livewire('sorteos/nuevo', Raffles\Create::class)
        ->name('raffles.create')
        ->can('create', Raffle::class);

    Route::livewire('sorteos/{raffle}', Raffles\Show::class)
        ->name('raffles.show')
        ->whereNumber('raffle')
        ->can('view', 'raffle');

    Route::get('sorteos/{raffle}/pdf', RaffleRecordController::class)
        ->name('raffles.pdf')
        ->whereNumber('raffle')
        ->can('download', 'raffle');

    Route::livewire('registro', Desk\Index::class)
        ->name('desk')
        ->can('useDesk', Assembly::class);

    Route::livewire('pantalla', Screen\Index::class)
        ->name('screen')
        ->can('watch', Projection::class);

    Route::post('pantalla/salida', LeaveScreenController::class)
        ->name('screen.leave')
        ->can('watch', Projection::class);
});

require __DIR__.'/settings.php';
