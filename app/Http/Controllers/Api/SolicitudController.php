<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreSolicitudRequest;
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
        if ($empleado->rol === 'Empleado') {
            return $solicitud->empleado_id === $empleado->id;
        }

        if ($empleado->rol === 'Jefe Inmediato') {
            return $solicitud->empleado_id === $empleado->id
                || Empleado::where('jefe_id', $empleado->id)->pluck('id')->contains($solicitud->empleado_id);
        }

        if ($empleado->rol === 'Gerente de Área') {
            return $solicitud->empleado_id === $empleado->id
                || Empleado::where('departamento_id', $empleado->departamento_id)->pluck('id')->contains($solicitud->empleado_id);
        }

        if ($empleado->rol === 'Gerente de Recursos Humanos') {
            return true;
        }

        if ($empleado->rol === 'Presidente') {
            return Empleado::where('id', $solicitud->empleado_id)
                ->where('cargo', 'like', '%Gerente%')
                ->exists();
        }

        return false;
    }

    private function puedeAprobar(Empleado $empleado, Solicitud $solicitud): bool
    {
        $solicitante = Empleado::find($solicitud->empleado_id);

        if (!$solicitante) {
            return false;
        }

        if ($empleado->rol === 'Jefe Inmediato') {
            // Solo aprueba solicitudes de sus empleados directos
            return $solicitante->jefe_id === $empleado->id;
        }

        if ($empleado->rol === 'Gerente de Área') {
            // Aprueba solicitudes del área (incluye empleados y jefes)
            return $solicitante->departamento_id === $empleado->departamento_id;
        }

        if ($empleado->rol === 'Gerente de Recursos Humanos') {
            return true;
        }

        if ($empleado->rol === 'Presidente') {
            return str_contains($solicitante->cargo, 'Gerente');
        }

        return false;
    }

    public function aprobarSolicitud($id)
    {
        $user = auth()->user();
        $empleado = $user->empleado;

        $solicitud = Solicitud::find($id);

        if (!$solicitud){
            return ApiResponse::error('Solicitud no encontrada',404);
        }

        if (!$this->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error("No autorizado para aprobar esta solicitud", 403);
        }

        if (!in_array($solicitud->estado, ['pendiente', 'aprobado_jefe', 'aprobado_gerente'])) {
            return ApiResponse::error('No se puede aprobar una solicitud finalizada.', 403);
        }

        DB::beginTransaction();
        try {
            if($empleado->rol === 'Jefe Inmediato'){
                $solicitud->estado = 'aprobado_jefe';
            }
            elseif ($empleado->rol=== 'Gerente de Área'){
                $solicitud->estado = 'aprobado_gerente';
            } elseif (in_array($empleado->rol, ['Gerente de Recursos Humanos', 'Presidente'])) {
                $solicitud->estado = 'aprobado_total';
            }

            $solicitud->save();
            DB::commit();

            return ApiResponse::success('Solicitud aprobada', $solicitud);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error al aprobar solicitud', ['error' => $e->getMessage()]);
            return ApiResponse::error("Error interno: " . $e->getMessage(), 500);
        }
    }

    public function rechazarSolicitud($id, Request $request): JsonResponse
    {
        $user = auth()->user();
        $empleado = $user->empleado;

        $solicitud = Solicitud::findOrFail($id);

        if (!$this->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error('No autorizado para rechazar esta solicitud.', 403);
        }

        $request->validate([
            'observacion_rechazo' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $solicitud->estado = 'rechazado';
            $solicitud->observacion_rechazo = $request->observacion_rechazo;
            $solicitud->save();

            DB::commit();
            return ApiResponse::success('Solicitud rechazada exitosamente', $solicitud);

        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Error al rechazar la solicitud', 500);
        }
    }

    public function index(Request $request)
    {
        $empleado = $request->user()->empleado;

        $query = Solicitud::where('estado_eliminado', 1);

        if ($empleado->rol === 'Empleado') {
            $query->where('empleado_id', $empleado->id);
        } elseif ($empleado->rol === 'Jefe Inmediato') {
            $ids = Empleado::where('jefe_id', $empleado->id)->pluck('id');
            $query->whereIn('empleado_id', $ids->push($empleado->id));
        } elseif ($empleado->rol === 'Gerente de Área') {
            $ids = Empleado::where('departamento_id', $empleado->departamento_id)->pluck('id');
            $query->whereIn('empleado_id', $ids->push($empleado->id));
        } elseif ($empleado->rol === 'Presidente') {
            $ids = Empleado::where('cargo', 'like', '%Gerente%')->pluck('id');
            $query->whereIn('empleado_id', $ids);
        }

        $solicitudes = $query->orderBy('fecha_solicitud', 'desc')->get();

        return ApiResponse::success('Solicitudes', $solicitudes);
    }

    public function show($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        $solicitud = Solicitud::find($id);

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

        $urlPdf = $archivoPath ? asset('storage/' . $archivoPath) : null;

        return ApiResponse::success('Solicitud registrada', [
            'solicitud' => $solicitud,
            'archivo_url' => $urlPdf
        ]);
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

