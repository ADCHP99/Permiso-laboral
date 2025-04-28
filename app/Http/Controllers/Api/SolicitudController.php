<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;


class SolicitudController extends Controller
{

    public function index(){
        $user = auth()->user();
        $empleadoId=$user->empleado_id;
        $solicitudes=Solicitud::where('estado_eliminado',1)
            ->orderBy('fecha_solicitud','desc')
            ->get();
        return response()->json(['solicitudes'=>$solicitudes]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $empleadoId=$user->empleado_id;
        $solicitud= Solicitud::where('empleado_id',$empleadoId)
            ->where('id',$id)
            ->first();
        if(!$solicitud){
            return response()->json(['error'=>'solicitud no encontrada'],404);

        }
        return response()->json(['solicitud'=>$solicitud]);
    }

    public function store(StoreSolicitudRequest $request)
{
    $user = $request->user();
    $data = $request->validated();

    $pdfContent = null;
    if ($request->hasFile('archivo_pdf')) {
        $pdfContent = file_get_contents($request->file('archivo_pdf')->getRealPath());
    }

    $solicitud = Solicitud::create([
        'empleado_id' => $user->empleado_id,
        'fecha_solicitud' => now(),
        'tipo_permiso' => $data['tipo_permiso'],
        'fecha_inicio' => $data['fecha_inicio'],
        'fecha_fin' => $data['fecha_fin']?? $data['fecha_inicio'],
        'hora_inicio' => $data['hora_inicio'],
        'hora_fin' => $data['hora_fin'],
        'motivo' => $data['motivo'],
        'descripcion' => $data['descripcion'],
        'archivo_pdf' => $pdfContent,
        'estado' => 'pendiente',
    ]);
    return response()->json(["message","Solicitud registrada",'solicitud' => $solicitud],201);
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
    $user = auth()->user();
    $empleadoId=$user->empleado_id;

    $solicitud = Solicitud::where('empleado_id',$empleadoId)
        ->where('id',$id)
        ->first();
    if(!$solicitud){
        return response()->json(['error'=>'solicitud no encontrada para actualizar'],404);

    }
    if($solicitud->tipo_permiso!=='pendiente'){
        return response()
            ->json(['error'=>'No se puede editar una solicitud que ya fue aprobada o rechazada.'],404);
    }
    $data = $request->validated();
    if($request->hasFile('archivo_pdf')){
        $solicitud->archivo_pdf = file_get_contents($request->file('archivo_pdf')->getRealPath());
    }
    $solicitud->update([
        'tipo_permiso' => $data['tipo_permiso'],
        'fecha_inicio' => $data['fecha_inicio'],
        'fecha_fin' => $data['fecha_fin'] ?? $data['fecha_inicio'],
        'hora_inicio' => $data['hora_inicio'],
        'hora_fin' => $data['hora_fin'],
        'motivo' => $data['motivo'],
        'descripcion' => $data['descripcion'],
    ]);
    $solicitud->save();
    return response()->json(["message","Solicitud actualizada",'solicitud' => $solicitud]);
}
public function destroy($id){
        $user = auth()->user();
        $empleadoId= $user->empleado_id;
        $solicitud = Solicitud::where('empleado_id',$empleadoId)
            ->where('id',$id)
            ->first();
        if(!$solicitud){
            return response()->json(['error'=>'solicitud no se pudo eliminar'],404);
        }
        if($solicitud->estado!=='pendiente'){

            return response()->json(["message"=>"No se puede eliminar una solicitud que ya fue aprobada o rechazada."]);

        }
        $solicitud->estado_eliminado=0;
        $solicitud->save();
        return response()->json([
        'message' => 'Solicitud eliminada correctamente.'
        ]);
}
}
