<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\EmpresaSelectorController;
use App\Http\Controllers\Equipo\EquipoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/seleccionar-empresa', [EmpresaSelectorController::class, 'create'])
        ->name('empresa.select');

    Route::post('/seleccionar-empresa', [EmpresaSelectorController::class, 'store'])
        ->name('empresa.select.store');
    
    Route::post('/cambiar-empresa', [EmpresaSelectorController::class, 'switch'])
    ->middleware('auth')
    ->name('empresa.switch');

});

// -------------------
// Zona privada (requiere login)
// -------------------
Route::middleware(['auth', 'verified', 'user.active', 'empresa.activa'])->group(function () {

    // Dashboard principal
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Exportaciones
    Route::get('export/usuarios', [ExportController::class, 'usuarios'])->name('export.usuarios');
    Route::get('export/auditoria', [ExportController::class, 'auditoria'])->name('export.auditoria');
    Route::get('export/equipos',   [ExportController::class, 'equipos'])->name('export.equipos');

    // Perfil del usuario
    Route::get('/profile/actividad', [ProfileController::class, 'profileActivity'])->name('profile.activity');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Usuarios
    Route::prefix('admin')->name('admin.')->middleware(['role:Administrador|Analista'])->group(function () {
        Route::resource('/usuarios', UsuarioController::class);
    });

    // Auditoria
    Route::prefix('admin')->name('admin.')->middleware(['role:Administrador|Analista'])->group(function () {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });

    // Equipos
    Route::prefix('admin')->name('admin.')->middleware(['role:Administrador|Analista'])->group(function () {
        Route::resource('/equipos', EquipoController::class);
    });


});

require __DIR__.'/auth.php';
