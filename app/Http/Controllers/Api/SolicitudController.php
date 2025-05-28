<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\Empleado;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudController extends Controller
{
    // --- Chequeo para APROBAR
    private function puedeAprobar(Empleado $empleado, Solicitud $solicitud): bool
    {
        $solicitante = $solicitud->empleado;
        if (!$solicitante) return false;

        if (in_array($solicitante->rol, ['Gerente de Área', 'Gerente de Recursos Humanos'])) {
            return $empleado->rol === 'Presidente';
        }

        if ($empleado->rol === 'Jefe Inmediato') {
            return $solicitante->jefe_id === $empleado->id;
        }

        if ($empleado->rol === 'Gerente de Área') {
            return $solicitante->departamento_id === $empleado->departamento_id;
        }

        if ($empleado->rol === 'Gerente de Recursos Humanos') {
            return true;
        }

        return false;
    }

    // --- Chequeo para VER
    private function puedeVer(Empleado $empleado, Solicitud $solicitud): bool
    {
        $solicitante = $solicitud->empleado;

        if ($empleado->id === $solicitante->id) {
            return true;
        }

        if ($empleado->rol === 'Jefe Inmediato') {
            return $solicitante->jefe_id === $empleado->id;
        }

        if ($empleado->rol === 'Gerente de Área') {
            return $solicitante->departamento_id === $empleado->departamento_id;
        }

        if ($empleado->rol === 'Gerente de Recursos Humanos') {
            return true;
        }

        if ($empleado->rol === 'Presidente') {
            return in_array($solicitante->rol, ['Gerente de Área', 'Gerente de Recursos Humanos']);
        }

        return false;
    }

    // --- LISTADO GENERAL para jefes/gerentes/rrhh/presidente
    public function index(Request $request)
    {
        $empleado = $request->user()->empleado;

        $solicitudes = Solicitud::with('empleado')
            ->where('estado_eliminado', 1)
            ->get()
            ->filter(fn($s) => $this->puedeVer($empleado, $s))
            ->sortByDesc('fecha_solicitud')
            ->values();

        return ApiResponse::success('Solicitudes', $solicitudes);
    }

    // --- VER DETALLE (para los que pueden ver)
    public function show($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::with('empleado')->find($id);

        if (!$solicitud || !$this->puedeVer($empleado, $solicitud)) {
            return ApiResponse::error("Solicitud no encontrada o sin permiso para verla", 403);
        }

        return ApiResponse::success('Solicitud', $solicitud);
    }

    // --- CREAR SOLICITUD (solo para el usuario logueado)
    public function store(StoreSolicitudRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $archivoPath = null;
        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            $archivoPath = $request->file('archivo_pdf')->store('solicitudes', 'public');
        }

        $solicitud = Solicitud::create([
            'empleado_id' => $user->empleado_id,
            'fecha_solicitud' => now(),
            'tipo_permiso' => $data['tipo_permiso'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? $data['fecha_inicio'],
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'motivo' => $data['motivo'],
            'descripcion' => $data['descripcion'],
            'archivo_pdf' => $archivoPath,
            'estado' => 'pendiente',
            'estado_eliminado' => 1
        ]);

        return ApiResponse::success('Solicitud registrada', [
            'solicitud' => $solicitud,
            'archivo_pdf' => $archivoPath ? asset("storage/$archivoPath") : null,
        ]);
    }

    // --- ACTUALIZAR (solo dueño, y si está pendiente)
    public function update(StoreSolicitudRequest $request, $id)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::error("Solicitud no encontrada o no autorizada para modificarla", 403);
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("No se puede editar una solicitud que ya fue procesada", 403);
        }

        $data = $request->validated();

        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            $archivoPath = $request->file('archivo_pdf')->store('solicitudes', 'public');
            $solicitud->archivo_pdf = $archivoPath;
        }

        $solicitud->update([
            'tipo_permiso' => $data['tipo_permiso'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] ?? $data['fecha_inicio'],
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'motivo' => $data['motivo'],
            'descripcion' => $data['descripcion'],
        ]);

        return ApiResponse::success("Solicitud actualizada correctamente", $solicitud);
    }

    // --- ELIMINAR (solo dueño y pendiente)
    public function destroy(Request $request, $id)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::error("Solicitud no encontrada o no autorizada para eliminarla", 403);
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("No se puede eliminar una solicitud que ya fue procesada", 403);
        }

        $solicitud->estado_eliminado = 0;
        $solicitud->save();

        return ApiResponse::success("Solicitud eliminada correctamente");
    }

    // --- APROBAR (según flujo de roles)
    public function aprobar($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::with('empleado')->find($id);

        if (!$solicitud) {
            return ApiResponse::error("Solicitud no encontrada", 404);
        }

        $solicitante = $solicitud->empleado;

        // Si ya fue finalizada
        if (in_array($solicitud->estado, ['rechazado', 'aprobado_total'])) {
            return ApiResponse::error("Solicitud ya fue finalizada", 403);
        }

        // Empleado común
        if ($solicitante->rol === 'Empleado') {
            if ($empleado->rol === 'Jefe Inmediato') {
                if ($solicitud->estado !== 'pendiente') {
                    return ApiResponse::error('Solo puede aprobar si está pendiente.', 403);
                }
                $solicitud->estado = 'aprobado_jefe';
            } elseif ($empleado->rol === 'Gerente de Área') {
                if ($solicitud->estado !== 'aprobado_jefe') {
                    return ApiResponse::error('Debe aprobar primero el Jefe Inmediato.', 403);
                }
                $solicitud->estado = 'aprobado_gerente';
            } elseif ($empleado->rol === 'Gerente de Recursos Humanos') {
                if ($solicitud->estado !== 'aprobado_gerente') {
                    return ApiResponse::error('Debe aprobar primero el Gerente de Área.', 403);
                }
                $solicitud->estado = 'aprobado_total';
            } else {
                return ApiResponse::error('No autorizado.', 403);
            }
        }
        // Jefe Inmediato solicita
        elseif ($solicitante->rol === 'Jefe Inmediato') {
            if ($empleado->rol === 'Gerente de Área') {
                if ($solicitud->estado !== 'pendiente') {
                    return ApiResponse::error('Solo puede aprobar si está pendiente.', 403);
                }
                $solicitud->estado = 'aprobado_gerente';
            } elseif ($empleado->rol === 'Gerente de Recursos Humanos') {
                if ($solicitud->estado !== 'aprobado_gerente') {
                    return ApiResponse::error('Debe aprobar primero el Gerente de Área.', 403);
                }
                $solicitud->estado = 'aprobado_total';
            } else {
                return ApiResponse::error('No autorizado.', 403);
            }
        }
        // Gerente de Área o Recursos Humanos solicita
        elseif (in_array($solicitante->rol, ['Gerente de Área', 'Gerente de Recursos Humanos'])) {
            if ($empleado->rol === 'Presidente') {
                if ($solicitud->estado !== 'pendiente') {
                    return ApiResponse::error('Solo puede aprobar si está pendiente.', 403);
                }
                $solicitud->estado = 'aprobado_total';
            } else {
                return ApiResponse::error('Solo el Presidente puede aprobar estas solicitudes.', 403);
            }
        } else {
            return ApiResponse::error('Rol de solicitante no reconocido.', 403);
        }

        // Guardar
        DB::beginTransaction();
        try {
            $solicitud->save();
            DB::commit();
            return ApiResponse::success("Solicitud aprobada", $solicitud);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al aprobar solicitud", ['error' => $e->getMessage()]);
            return ApiResponse::error("Error interno al aprobar la solicitud", 500);
        }
    }

    // --- RECHAZAR
    public function rechazar($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::with('empleado')->find($id);

        if (!$solicitud || !$this->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error("No autorizado para rechazar esta solicitud", 403);
        }

        $request->validate([
            'observacion_rechazo' => 'required|string|max:500'
        ]);

        $solicitud->estado = 'rechazado';
        $solicitud->observacion_rechazo = $request->observacion_rechazo;
        $solicitud->save();

        return ApiResponse::success("Solicitud rechazada", $solicitud);
    }

    // --- CRUD de MIS solicitudes (empleado común)
    public function meSolicitudes(Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitudes = Solicitud::where('empleado_id', $empleado->id)
            ->where('estado_eliminado', 1)
            ->orderBy('id', 'asc')->get();
        return ApiResponse::success('Mis solicitudes', $solicitudes);
    }
    public function showMe($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::error("Solicitud no encontrada o no autorizada", 403);
        }
        return ApiResponse::success('Solicitud', $solicitud);
    }

}

