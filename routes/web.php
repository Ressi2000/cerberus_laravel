<?php

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/', function () {
    return view('welcome');
});

// -------------------
// Zona privada (requiere login)
// -------------------
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard principal
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('export/usuarios', [ExportController::class, 'usuarios'])
    ->name('export.usuarios');


    // Perfil del usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de administración
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:Administrador|Analista')
        ->group(function () {
            Route::resource('/usuarios', UsuarioController::class);
        });
});

require __DIR__.'/auth.php';
