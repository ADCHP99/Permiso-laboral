<?php

use App\Http\Controllers\Api\AprobacionController;
use App\Http\Controllers\Api\SolicitudController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthController::class,'login'])->name('login');
Route::middleware(['force.json', 'auth.custom', 'format.validation'])->group(function () {
    Route::get('/me', function (Request $request) {
        $user = $request->user();
        // Cargar la relación empleado (si quieres mostrarla)
        $user->load('empleado');

        return response()->json([
            'id' => $user->id,
            'nombre_usuario' => $user->nombre_usuario,
            'empleado' => [
                'nombre' => $user->empleado?->nombre,
                'apellido' => $user->empleado?->apellido,
                'cedula' => $user->empleado?->cedula,
                'cargo' => $user->empleado?->cargo,
                'rol' => $user->empleado?->rol,
            ]
        ]);
    });
    Route::post('/logout',[AuthController::class,'logout']);

    Route::get('/solicitudes',[SolicitudController::class,'index']);
    Route::post('/solicitudes', [SolicitudController::class, 'store']);
    Route::get('/solicitudes/{id}', [SolicitudController::class, 'show']);
    Route::put('/solicitudes/{id}', [SolicitudController::class, 'update']);
    Route::delete('/solicitudes/{id}', [SolicitudController::class, 'destroy']);

    //Route::apiResource('/solicitudes', SolicitudController::class);
    Route::post('/aprobaciones/{solicitud}/aprobar', [AprobacionController::class, 'aprobar']);
    Route::post('/aprobaciones/{solicitud}/rechazar', [AprobacionController::class, 'rechazar']);
   // Route::get('/aprobaciones/{solicitud}', [AprobacionController::class, 'historial']);
    Route::get('/aprobaciones', [AprobacionController::class, 'index']);
    Route::get('/aprobaciones/{solicitud}', [AprobacionController::class, 'verDetalle']);
});
