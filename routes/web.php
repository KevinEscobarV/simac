<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'inicio')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('inicio', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
