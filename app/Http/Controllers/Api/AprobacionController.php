<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\Aprobacion;
use App\Models\Empleado;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AprobacionController extends Controller
{
    private function nivelAprobacion(Empleado $empleado): ?string
    {
        return match ($empleado->rol) {
            'Jefe Inmediato' => 'jefe',
            'Gerente de Área' => 'gerente_area',
            'Gerente de Recursos Humanos' => 'rrhh',
            'Presidente' => 'presidencia',
            default => null,
        };
    }

    private function puedeAprobar(Empleado $empleado, Solicitud $solicitud): bool
    {
        $solicitante = Empleado::find($solicitud->empleado_id);
        if (!$solicitante) return false;

        // Presidente solo aprueba solicitudes de gerentes
        if ($empleado->rol === 'Presidente') {
            return in_array($solicitante->rol, ['Gerente de Área', 'Gerente de Recursos Humanos']);
        }

        // RRHH solo si ya aprobó gerente_area
        if ($empleado->rol === 'Gerente de Recursos Humanos') {
            return Aprobacion::where('solicitud_id', $solicitud->id)
                ->where('nivel', 'gerente_area')
                ->where('resultado', 'aprobada')
                ->exists();
        }

        // Gerente solo si ya aprobó jefe
        if ($empleado->rol === 'Gerente de Área') {
            return Aprobacion::where('solicitud_id', $solicitud->id)
                ->where('nivel', 'jefe')
                ->where('resultado', 'aprobada')
                ->exists();
        }

        // Jefe solo si el solicitante es su subordinado
        if ($empleado->rol === 'Jefe Inmediato') {
            return $solicitante->jefe_id === $empleado->id;
        }

        return false;
    }

    public function aprobar($id)
    {
        $empleado = auth()->user()->empleado;
        $nivel = $this->nivelAprobacion($empleado);

        if (!$nivel) return ApiResponse::error("No autorizado", 403);

        $solicitud = Solicitud::find($id);
        if (!$solicitud) return ApiResponse::error("Solicitud no encontrada", 404);

        if (!$this->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error("No autorizado para aprobar esta solicitud", 403);
        }

        DB::beginTransaction();
        try {
            Aprobacion::updateOrCreate(
                [
                    'solicitud_id' => $solicitud->id,
                    'nivel' => $nivel,
                ],
                [
                    'aprobador_id' => $empleado->id,
                    'resultado' => 'aprobada',
                    'fecha_aprobacion' => now(),
                ]
            );

            // Si todos los niveles requeridos aprobaron, actualizar solicitud
            $aprobaciones = Aprobacion::where('solicitud_id', $solicitud->id)->get();
            $nivelesNecesarios = ['jefe', 'gerente_area', 'rrhh'];

            $aprobados = $aprobaciones->whereIn('nivel', $nivelesNecesarios)
                ->where('resultado', 'aprobada')
                ->pluck('nivel')
                ->toArray();

            if (count(array_intersect($nivelesNecesarios, $aprobados)) === count($nivelesNecesarios)) {
                $solicitud->estado = 'aprobado_total';
                $solicitud->save();
            }

            DB::commit();
            return ApiResponse::success('Solicitud aprobada correctamente.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al aprobar solicitud', ['error' => $e->getMessage()]);
            return ApiResponse::error('Error interno al aprobar solicitud.', 500);
        }
    }

    public function rechazar($id, Request $request)
    {
        $empleado = auth()->user()->empleado;
        $nivel = $this->nivelAprobacion($empleado);

        if (!$nivel) return ApiResponse::error('No autorizado para rechazar.', 403);

        $solicitud = Solicitud::find($id);
        if (!$solicitud) return ApiResponse::error('Solicitud no encontrada.', 404);

        if (!$this->puedeAprobar($empleado, $solicitud)) {
            return ApiResponse::error('No autorizado para rechazar esta solicitud.', 403);
        }

        $request->validate([
            'observacion' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            Aprobacion::updateOrCreate(
                [
                    'solicitud_id' => $solicitud->id,
                    'nivel' => $nivel,
                ],
                [
                    'aprobador_id' => $empleado->id,
                    'resultado' => 'rechazada',
                    'observacion' => $request->observacion,
                    'fecha_aprobacion' => now(),
                ]
            );

            $solicitud->estado = 'rechazado';
            $solicitud->save();

            DB::commit();
            return ApiResponse::success('Solicitud rechazada correctamente.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al rechazar solicitud', ['error' => $e->getMessage()]);
            return ApiResponse::error('Error interno al rechazar solicitud.', 500);
        }
    }

    public function index(Request $request)
    {
        $empleado = $request->user()->empleado;

        $aprobaciones = Aprobacion::where('aprobador_id', $empleado->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success('Lista de aprobaciones', $aprobaciones);
    }

    public function verDetalle(Solicitud $solicitud)
    {
        $solicitud->load('aprobaciones.aprobador');

        return ApiResponse::success('Detalle de la solicitud con sus aprobaciones', $solicitud);
    }
}
