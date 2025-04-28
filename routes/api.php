<?php

use App\Http\Controllers\Api\SolicitudController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthController::class,'login']);
Route::middleware('auth:sanctum')->group(function () {
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
    Route::post('/solicitudes', [SolicitudController::class, 'store']);
    Route::get('/solicitudes/{id}/archivo', [SolicitudController::class, 'download']);
    Route::get('/solicitudes/{id}', [SolicitudController::class, 'show']);
    Route::put('/solicitudes/{id}', [SolicitudController::class, 'update']);
    Route::delete('/solicitudes/{id}', [SolicitudController::class, 'destroy']);
});
