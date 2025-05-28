<?php

use App\Http\Controllers\Api\SolicitudController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// LOGIN Y LOGOUT
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['force.json', 'auth.custom', 'format.validation'])->group(function () {

    // Datos del usuario autenticado
    Route::get('/me', function (Request $request) {
        $user = $request->user();
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

    Route::post('/logout', [AuthController::class, 'logout']);

    // -------- CRUD PERSONAL DE SOLICITUDES --------
    Route::get('/me/solicitudes', [SolicitudController::class, 'meSolicitudes']);     // Listar propias
    Route::post('/me/solicitudes', [SolicitudController::class, 'store']);            // Crear propia
    Route::get('/me/solicitudes/{id}', [SolicitudController::class, 'showMe']);       // Ver propia
    Route::put('/me/solicitudes/{id}', [SolicitudController::class, 'update']);       // Editar propia
    Route::delete('/me/solicitudes/{id}', [SolicitudController::class, 'destroy']);   // Eliminar propia

    // -------- FLUJO DE REVISIÓN/APROBACIÓN (JEFES, GERENTES, ETC) --------
    Route::get('/solicitudes', [SolicitudController::class, 'index']);                // Listar a revisar/aprobar
    Route::get('/solicitudes/{id}', [SolicitudController::class, 'show']);            // Ver detalle (solo si tienes acceso)
    Route::post('/solicitudes/{id}/aprobar', [SolicitudController::class, 'aprobar']); // Aprobar
    Route::post('/solicitudes/{id}/rechazar', [SolicitudController::class, 'rechazar']); // Rechazar

});
