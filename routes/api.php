<?php

use App\Http\Controllers\Api\SolicitudController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// LOGIN Y LOGOUT
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['force.json', 'auth.custom', 'format.validation'])->group(function () {

    // Datos del usuario autenticado
    Route::get('/me', [AuthController::class, 'me'] )->name('me');

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('me')->group(function () {
        Route::get('/solicitudes', [SolicitudController::class, 'meSolicitudes']);       // Listar propias
        Route::post('/solicitudes', [SolicitudController::class, 'store']);              // Crear propia
        Route::get('/solicitudes/{id}', [SolicitudController::class, 'showMe']);         // Ver propia
        Route::post('/solicitudes/{id}', [SolicitudController::class, 'update']);         // Editar propia
        Route::delete('/solicitudes/{id}', [SolicitudController::class, 'destroy']);     // Eliminar propia
    });

    Route::prefix('solicitudes')->group(function () {
        Route::get('/', [SolicitudController::class, 'index']);                          // Listar a revisar/aprobar
        Route::get('/{id}', [SolicitudController::class, 'show']);                       // Ver detalle (solo si tienes acceso)
        Route::post('/{id}/aprobar', [SolicitudController::class, 'aprobar']);           // Aprobar
        Route::post('/{id}/rechazar', [SolicitudController::class, 'rechazar']);         // Rechazar
    });
});
