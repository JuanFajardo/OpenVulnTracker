<?php

use App\Http\Controllers\Api\ImportController as ApiImportController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DependencyController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SastController;
use App\Http\Controllers\TrivyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VulnerabilityStateController;
use Illuminate\Support\Facades\Route;

// ─── API pública — sin sesión, sin CSRF, autenticada por ApiTokenAuth ──────
Route::post('/api/import', [ApiImportController::class, 'store'])
    ->middleware(\App\Http\Middleware\ApiTokenAuth::class)
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.import');

// ─── Redirección raíz ────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('projects.index'));

// ─── Rutas protegidas (sesión web) ───────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

    // Project submenu (landing)
    Route::get('/projects/{project}', [ProjectController::class, 'menu'])->name('projects.show');

    // Historial de escaneos (agrupado por rama)
    Route::get('/projects/{project}/history', [ProjectController::class, 'history'])->name('projects.history');

    // Detalle de un escaneo específico (desde historial)
    Route::get('/projects/{project}/scans/{scan}', [ProjectController::class, 'showScan'])->name('projects.scans.show');

    // SCA — último escaneo por rama, con filtro ?branch=
    Route::get('/projects/{project}/sca', [ProjectController::class, 'sca'])->name('projects.sca');

    // Trivy — contenedor/imagen
    Route::get('/projects/{project}/trivy',                     [TrivyController::class, 'index'])      ->name('projects.trivy');
    Route::get('/projects/{project}/trivy/{trivyScan}',         [TrivyController::class, 'showScan'])   ->name('projects.trivy.show');
    Route::delete('/projects/{project}/trivy/{trivyScan}',      [TrivyController::class, 'destroyScan'])->name('projects.trivy.destroy');

    // SAST — análisis estático (Semgrep)
    Route::get('/projects/{project}/sast',                      [SastController::class, 'index'])       ->name('projects.sast');
    Route::get('/projects/{project}/sast/{sastScan}',           [SastController::class, 'showScan'])    ->name('projects.sast.show');
    Route::delete('/projects/{project}/sast/{sastScan}',        [SastController::class, 'destroyScan']) ->name('projects.sast.destroy');

    // Eliminar
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::delete('/projects/{project}/scans/{scan}', [ProjectController::class, 'destroyScan'])->name('projects.scans.destroy');

    // Detalle de dependencia
    Route::get('/projects/{project}/dependencies/{dependency}', [DependencyController::class, 'show'])
        ->name('dependencies.show');

    // Estado de vulnerabilidad
    Route::post('/vulnerabilities/{vulnerability}/state', [VulnerabilityStateController::class, 'update'])
        ->name('vulnerabilities.state');

    // Importar reporte
    Route::get('/import', [ImportController::class, 'create'])->name('import.create');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');

    // Gestión de usuarios
    Route::resource('users', UserController::class)->except(['show']);

    // Gestión de tokens API (admin only)
    Route::get('/tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
    Route::put('/tokens/{token}', [ApiTokenController::class, 'update'])->name('tokens.update');
    Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
