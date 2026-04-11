<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\DepartamentoController;
use App\Http\Controllers\Asignaciones\AsignacionController;
use App\Http\Controllers\Usuario\UsuarioController;
use App\Http\Controllers\Auth\EmpresaSelectorController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Equipo\EquipoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Usuario\ProfileController;
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
    Route::get('export/usuarios',      [ExportController::class, 'usuarios'])->name('export.usuarios');
    Route::get('export/auditoria',     [ExportController::class, 'auditoria'])->name('export.auditoria');
    Route::get('export/equipos',       [ExportController::class, 'equipos'])->name('export.equipos');

    // Solo Administrador
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('export/categorias',    [ExportController::class, 'categorias'])->name('export.categorias');
        Route::get('export/estados',       [ExportController::class, 'estados'])->name('export.estados');
        Route::get('export/atributos',     [ExportController::class, 'atributos'])->name('export.atributos');
        Route::get('export/ubicaciones',   [ExportController::class, 'ubicaciones'])->name('export.ubicaciones');
        Route::get('export/cargos',        [ExportController::class, 'cargos'])->name('export.cargos');
        Route::get('export/departamentos', [ExportController::class, 'departamentos'])->name('export.departamentos');
        Route::get('export/empresas',      [ExportController::class, 'empresas'])->name('export.empresas');
    });
    
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

    // Configuración (solo para Administrador)
    Route::prefix('admin/configuracion')->name('admin.configuracion.')
        ->middleware(['role:Administrador'])
        ->group(function () {
            Route::get('/categorias',  [ConfiguracionController::class, 'categorias'])->name('categorias');
            Route::get('/estados',     [ConfiguracionController::class, 'estados'])->name('estados');
            Route::get('/atributos',   [ConfiguracionController::class, 'atributos'])->name('atributos');
            Route::get('/departamentos', [ConfiguracionController::class, 'departamentos'])->name('departamentos');
            Route::get('/cargos',      [ConfiguracionController::class, 'cargos'])->name('cargos');
            Route::get('/ubicaciones', [ConfiguracionController::class, 'ubicaciones'])->name('ubicaciones');
            Route::get('/empresas',    [ConfiguracionController::class, 'empresas'])->name('empresas');
        });

    Route::prefix('admin/asignaciones')
        ->name('admin.asignaciones.')
        ->middleware(['auth', 'verified', 'user.active', 'empresa.activa', 'role:Administrador|Analista'])
        ->group(function () {

            // Vistas principales
            Route::get('/',                              [AsignacionController::class, 'index'])->name('index');
            Route::get('/crear',                         [AsignacionController::class, 'create'])->name('create');
            Route::get('/historial/{usuario}',           [AsignacionController::class, 'historial'])->name('historial');
            Route::get('/devolver/usuario/{usuario}',    [AsignacionController::class, 'devolverUsuario'])->name('devolver.usuario');
            Route::get('/{asignacion}/devolver',         [AsignacionController::class, 'devolver'])->name('devolver');

            // Planillas PDF
            Route::get('/{asignacion}/planilla/asignacion', [AsignacionController::class, 'planillaAsignacion'])->name('planilla.asignacion');
            Route::get('/{asignacion}/planilla/devolucion', [AsignacionController::class, 'planillaDevolucion'])->name('planilla.devolucion');
            Route::get('/planilla/egreso/{usuario}',        [AsignacionController::class, 'planillaEgreso'])->name('planilla.egreso');
        });
});

require __DIR__ . '/auth.php';
