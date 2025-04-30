<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\Empleado;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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


    public function show($id,Request $request)
    {
        $empleado=$request->user()->empleado;
        $solicitud= Solicitud::find($id);

        if(!$solicitud || !$this->puedeAccederSolicitud($solicitud, $empleado)){
            return ApiResponse::error("Solicitud no encontrada o sin permiso para verla",403);


        }
        return ApiResponse::success('Solicitud',$solicitud);
    }

    /**
     * @param StoreSolicitudRequest $request
     * @return JsonResponse
     */
    public function store(StoreSolicitudRequest $request):JsonResponse
    {
    $user = $request->user();
    $data = $request->validated();

    $archivoPath = null;
    if ($request->hasFile('archivo_pdf')) {
        $archivoPath = $request->file('archivo_pdf')->store('solicitudes', 'public');
        $file = $request->file('archivo_pdf');
        Log::info('Nombre original:', ['name' => $file->getClientOriginalName()]);
        Log::info('Mime type:', ['mime' => $file->getMimeType()]);
        Log::info('Tamaño:', ['size' => $file->getSize()]);
        Log::info('¿Es válido?', ['valido' => $file->isValid()]);

        $archivoPath = $file->store('solicitudes', 'public');
        Log::info('Archivo guardado en:', ['archivoPath' => $archivoPath]);
    }

    $solicitud = Solicitud::create([
        'empleado_id' => $user->empleado_id,
        'fecha_solicitud' => now(),
        'tipo_permiso' => $data['tipo_permiso'],
        'fecha_inicio' => $data['fecha_inicio'],
        'fecha_fin' => $data['fecha_fin']?? $data['fecha_inicio'],
        'hora_inicio' => $data['hora_inicio']??null,
        'hora_fin' => $data['hora_fin']??null,
        'motivo' => $data['motivo'],
        'descripcion' => $data['descripcion'],
        'archivo_pdf' => $archivoPath,
        'estado' => 'pendiente',
        'estado_eliminado' => 1
    ]);
    Log::info('Archivo recibido:', ['archivo_pdf' => $request->file('archivo_pdf')]);

        $urlPdf = $archivoPath ? asset('storage/' . $archivoPath) : null;

        return ApiResponse::success('Solicitud registada',[
            'solicitud' => $solicitud,
            'archivo_url' => $urlPdf
        ]);
    }
public function download($id){
    $solicitud= Solicitud::findOrFail($id);
    if(!$solicitud->archivo_pdf){
        return response()->json(["message","No existe archivo para esta solicitud"],404);
    }
    return Response::make($solicitud->archivo_pdf,200,[
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="solicitud_' . $solicitud->id . '.pdf"',
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

    if ($request->hasFile('archivo_pdf')) {
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
public function destroy(Request $request, $id){
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

}

