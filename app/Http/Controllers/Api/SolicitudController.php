<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\Empleado;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class SolicitudController extends Controller
{
    private function puedeAccederSolicitud(Solicitud $solicitud, Empleado $empleado): bool
    {
        // Solo mostrar solicitudes según nivel de aprobación alcanzado
        $nivelesAprobados = $solicitud->aprobaciones
            ->where('resultado', 'aprobada')
            ->pluck('nivel')
            ->toArray();

        $visibilidad = match ($empleado->rol) {
            'Empleado' => [$solicitud->empleado_id === $empleado->id],
            'Jefe Inmediato' => in_array('jefe', $nivelesAprobados) || $solicitud->empleado->jefe_id === $empleado->id,
            'Gerente de Área' => in_array('gerente_area', $nivelesAprobados) || $solicitud->empleado->departamento_id === $empleado->departamento_id,
            'Gerente de Recursos Humanos' => in_array('rrhh', $nivelesAprobados),
            'Presidente' => in_array($solicitud->empleado->rol, ['Gerente de Área', 'Gerente de Recursos Humanos']),
            default => false
        };

        return is_array($visibilidad) ? $visibilidad[0] : $visibilidad;
    }

    public function index(Request $request)
    {
        $empleado = $request->user()->empleado;

        $solicitudes = Solicitud::with('aprobaciones')
            ->where('estado_eliminado', 1)
            ->get()
            ->filter(fn($s) => $this->puedeAccederSolicitud($s, $empleado))
            ->sortByDesc('fecha_solicitud')
            ->values();

        return ApiResponse::success('Solicitudes', $solicitudes);
    }

    public function show($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::with('aprobaciones')->find($id);

        if (!$solicitud || !$this->puedeAccederSolicitud($solicitud, $empleado)) {
            return ApiResponse::error("Solicitud no encontrada o sin permiso para verla", 403);
        }

        return ApiResponse::success('Solicitud', $solicitud);
    }

    public function store(StoreSolicitudRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $archivoPath = null;
        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            $archivoPath = $request->file('archivo_pdf')->store('solicitudes', 'public');
            Log::info('Archivo guardado en:', ['archivoPath' => $archivoPath]);
        }

        DB::beginTransaction();
        try {
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

            // Registrar aprobaciones necesarias
            $aprobaciones = [
                ['nivel' => 'jefe'],
                ['nivel' => 'gerente_area'],
                ['nivel' => 'rrhh']
            ];

            foreach ($aprobaciones as $aprobacion) {
                $solicitud->aprobaciones()->create([
                    'nivel' => $aprobacion['nivel'],
                    'resultado' => 'pendiente'
                ]);
            }

            DB::commit();

            $urlPdf = $archivoPath ? asset('storage/' . $archivoPath) : null;

            return ApiResponse::success('Solicitud registrada', [
                'solicitud' => $solicitud,
                'archivo_url' => $urlPdf
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear solicitud', ['error' => $e->getMessage()]);
            return ApiResponse::error('Error interno al registrar la solicitud.', 500);
        }
    }

    public function update(StoreSolicitudRequest $request, $id)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

        if (!$solicitud || !$this->puedeAccederSolicitud($solicitud, $empleado)) {
            return ApiResponse::error("Solicitud no encontrada o sin permisos para actualizarla", 403);
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("Solicitud no se puede actualizar porque su estado no es pendiente");
        }

        $data = $request->validated();

        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            $solicitud->archivo_pdf = $request->file('archivo_pdf')->store('solicitudes', 'public');
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

        return ApiResponse::success("Solicitud actualizada", $solicitud);
    }

    public function destroy(Request $request, $id)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

        if (!$solicitud || !$this->puedeAccederSolicitud($solicitud, $empleado)) {
            return ApiResponse::error("Solicitud no encontrada o sin permisos para eliminarla", 403);
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("No se puede eliminar una solicitud que ya fue aprobada o rechazada.");
        }

        $solicitud->estado_eliminado = 0;
        $solicitud->save();

        return ApiResponse::success("Solicitud eliminada", $solicitud);
    }

    public function download($id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (!$solicitud->archivo_pdf) {
            return ApiResponse::error('No existe archivo para esta solicitud', 404);
        }

        $path = storage_path('app/public/' . $solicitud->archivo_pdf);

        if (!file_exists($path)) {
            return ApiResponse::error('Archivo no encontrado en el servidor', 404);
        }

        return response()->download($path);
    }
}
