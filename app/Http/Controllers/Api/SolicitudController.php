<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\Empleado;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudController extends Controller
{
    protected $solicitudService;

    public function __construct(SolicitudService $solicitudService)
    {$this->solicitudService = $solicitudService;}

    // --- LISTADO GENERAL para jefes/gerentes/rrhh/presidente
    public function index(Request $request)
    {
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");

        $perPage = $request->input('perPage', 5);

        $query = Solicitud::with('empleado')->where('estado_eliminado', 1);

        switch ($empleado->rol) {
            case 'Jefe Inmediato':
                $idsSubordinados = Empleado::where('jefe_id', $empleado->id)->pluck('id');
                $query->whereIn('empleado_id', $idsSubordinados);
                break;
            case 'Gerente de Área':
                $idsDepartamento = Empleado::where('departamento_id', $empleado->departamento_id)
                    ->where('id', '!=', $empleado->id)
                    ->pluck('id');
                $query->whereIn('empleado_id', $idsDepartamento);
                break;
            case 'Gerente de Recursos Humanos':
                $query->where('empleado_id', '!=', $empleado->id);
                break;
            case 'Presidente':
                $idsGerentes = Empleado::whereIn('rol', ['Gerente de Área', 'Gerente de Recursos Humanos'])
                    ->where('id', '!=', $empleado->id)
                    ->pluck('id');
                $query->whereIn('empleado_id', $idsGerentes);
                break;
            default:
                $query->whereRaw('1=0');
                break;
        }

        $paginator = $query->orderByDesc('fecha_solicitud')->paginate($perPage);

        return ApiResponse::paginated($paginator, 'Solicitudes');
    }
    // --- VER DETALLE (para los que pueden ver)
    public function show($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");

        $solicitud = Solicitud::with('empleado')->find($id);

        if (!$solicitud || !$this->solicitudService->puedeVer($empleado, $solicitud)) {
            return ApiResponse::notFound("Solicitud no encontrada o sin permiso para verla");
        }

        return ApiResponse::success('Solicitud', $solicitud);
    }

    // --- CREAR SOLICITUD (solo para el usuario logueado)
    public function store(StoreSolicitudRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        if(!$user || !$user->empleado_id ) return ApiResponse::unauthorized("Usuario sin empleado asociado");

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

        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");

        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::notFound("Solicitud no encontrada o no autorizada para modificarla");
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("No se puede editar una solicitud que ya fue procesada", 403);
        }

        $data = $request->validated();

        if ($request->hasFile('archivo_pdf') && $request->file('archivo_pdf')->isValid()) {
            // (Opcional) Elimina el archivo anterior
            if ($solicitud->archivo_pdf) {
                \Storage::disk('public')->delete($solicitud->archivo_pdf);
            }
            $archivoPath = $request->file('archivo_pdf')->store('solicitudes', 'public');
            $data['archivo_pdf'] = $archivoPath;
        } else {
            unset($data['archivo_pdf']);
        }

        $solicitud->update($data);

        $archivoUrl = isset($data['archivo_pdf'])
            ? asset("storage/{$data['archivo_pdf']}")
            : ($solicitud->archivo_pdf ? asset("storage/{$solicitud->archivo_pdf}") : null);

        return ApiResponse::success('Solicitud actualizada', [
            'solicitud' => $solicitud,
            'archivo_pdf' => $archivoUrl,
        ]);
    }

    // --- ELIMINAR (solo dueño y pendiente)
    public function destroy(Request $request, $id)
    {
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized('Usuario no autenticado.');
        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::notFound("Solicitud no encontrada o no autorizada para eliminarla");
        }

        if ($solicitud->estado !== 'pendiente') {
            return ApiResponse::error("No se puede eliminar una solicitud que ya fue procesada", 403);
        }

        $solicitud->estado_eliminado = 0;
        $solicitud->save();

        return ApiResponse::success("Solicitud eliminada correctamente");
    }

    public function aprobar($id,Request $request){
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");
        $solicitud=Solicitud::with('empleado')->find($id);
        if (!$solicitud) return ApiResponse::notFound("Solicitud no encontrada");
        if (!$this->solicitudService->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error('No autorizado para aprobar esta solicitud', 403);
        }
        if (!$this->solicitudService->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error('No autorizado para aprobar esta solicitud', 403);
        }
        try {
            $solicitud= $this->solicitudService->aprobacion($empleado,$solicitud);
            return ApiResponse::success("Solicitud aprobada", $solicitud);
        }catch (\Exception $e){
            return ApiResponse::error("Error al aprobar solicitud", ['error' => $e->getMessage()],403);
        }
    }

    public function rechazar($id,Request $request){
        $empleado = $request->user()->empleado;

        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");
        $solicitud=Solicitud::with('empleado')->find($id);
        if (!$solicitud) return ApiResponse::notFound("Solicitud no encontrada");
        if (!$this->solicitudService->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error('No autorizado para rechazar esta solicitud', 403);
        }

        $request->validate(['observacion_rechazo' => 'required|string|max:255',],
            [
                'observacion_rechazo.required' => 'La observacion es requerida',
                'observacion_rechazo.string' => 'La observacion del rechazo debe ser texto',
            ]);

        try{
            $solicitud= $this->solicitudService->rechazar($empleado,$solicitud,$request->observacion_rechazo);
            return ApiResponse::success("Solicitud rechazada", $solicitud);

        }catch (\Exception $e){
            return ApiResponse::error("Error al aprobar solicitud", ['error' => $e->getMessage()],403);
        }

    }


    // --- CRUD de MIS solicitudes (empleado común)
    public function meSolicitudes(Request $request)
    {
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");

        $perPage = $request->input('per_page', 5);

        $paginator = Solicitud::where('empleado_id', $empleado->id)
            ->where('estado_eliminado', 1)
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        return ApiResponse::paginated($paginator, 'Mis solicitudes');
    }
    public function showMe($id, Request $request)
    {
        $empleado = $request->user()->empleado;
        if (!$empleado) return ApiResponse::unauthorized("Usuario no autorizado");
        $solicitud = Solicitud::find($id);

        if (!$solicitud || $solicitud->empleado_id !== $empleado->id) {
            return ApiResponse::notFound("Solicitud no encontrada o no autorizada");
        }
        return ApiResponse::success('Solicitud', $solicitud);
    }

}

