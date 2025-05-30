<?php

namespace App\Services;
use App\Models\Empleado;
use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;


class SolicitudService
{
    public function puedeAprobar(Empleado $empleado, Solicitud $solicitud): bool
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

// --- APROBAR (según flujo de roles)
    public function aprobacion(Empleado $aprobador, Solicitud $solicitud)
    {
        if (in_array($solicitud->estado, ['rechazado', 'aprobado_total'])) {
            throw new \Exception("Solicitud ya fue finalizada");
        }

        $solicitante = $solicitud->empleado;

        if ($solicitante->rol === 'Empleado') {
            if ($aprobador->rol === 'Jefe Inmediato') {
                if ($solicitud->estado !== 'pendiente') throw new \Exception('Solo puede aprobar si está pendiente.');
                $solicitud->estado = 'aprobado_jefe';
            } elseif ($aprobador->rol === 'Gerente de Área') {
                if ($solicitud->estado !== 'aprobado_jefe') throw new \Exception('Debe aprobar primero el Jefe Inmediato.');
                $solicitud->estado = 'aprobado_gerente';
            } elseif ($aprobador->rol === 'Gerente de Recursos Humanos') {
                if ($solicitud->estado !== 'aprobado_gerente') throw new \Exception('Debe aprobar primero el Gerente de Área.');
                $solicitud->estado = 'aprobado_total';
            } else {
                throw new \Exception('No autorizado.');
            }
        }
        elseif ($solicitante->rol === 'Jefe Inmediato') {
            if ($aprobador->rol === 'Gerente de Área') {
                if ($solicitud->estado !== 'pendiente') throw new \Exception('Solo puede aprobar si está pendiente.');
                $solicitud->estado = 'aprobado_gerente';
            } elseif ($aprobador->rol === 'Gerente de Recursos Humanos') {
                if ($solicitud->estado !== 'aprobado_gerente') throw new \Exception('Debe aprobar primero el Gerente de Área.');
                $solicitud->estado = 'aprobado_total';
            } else {
                throw new \Exception('No autorizado.');
            }
        }
        elseif (in_array($solicitante->rol, ['Gerente de Área', 'Gerente de Recursos Humanos'])) {
            if ($aprobador->rol === 'Presidente') {
                if ($solicitud->estado !== 'pendiente') throw new \Exception('Solo puede aprobar si está pendiente.');
                $solicitud->estado = 'aprobado_total';
            } else {
                throw new \Exception('Solo el Presidente puede aprobar estas solicitudes.');
            }
        } else {
            throw new \Exception('Rol de solicitante no reconocido.');
        }

        DB::transaction(function() use ($solicitud) {
            $solicitud->save();
        });

        return $solicitud;
    }

    // --- RECHAZAR
    public function rechazar(Empleado $aprobador, Solicitud $solicitud, $motivo)
    {
        if (in_array($solicitud->estado, ['rechazado', 'aprobado_total'])) {
            throw new \Exception("Solicitud ya fue finalizada");
        }

        $solicitud->estado = 'rechazado';
        $solicitud->observacion_rechazo = $motivo;
        $solicitud->save();

        return $solicitud;
    }
    public function puedeVer(Empleado $empleado, Solicitud $solicitud): bool
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



}
