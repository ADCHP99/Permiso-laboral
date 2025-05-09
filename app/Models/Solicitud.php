<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Solicitud extends Model
{
    protected $table = 'solicitudes';
    protected $fillable = [
        'empleado_id', 'fecha_solicitud', 'tipo_permiso', 'fecha_inicio', 'fecha_fin',
        'hora_inicio', 'hora_fin', 'motivo', 'descripcion', 'archivo_pdf', 'estado','estado_eliminado'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function aprobaciones()
    {
        return $this->hasMany(Aprobacion::class, 'solicitud_id');
    }

}
