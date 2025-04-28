<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $fillable = [
        'empleado_id', 'fecha_solicitud', 'tipo_permiso', 'fecha_inicio', 'fecha_fin',
        'hora_inicio', 'hora_fin', 'motivo', 'descripcion', 'archivo_pdf', 'estado','estado_eliminado'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
