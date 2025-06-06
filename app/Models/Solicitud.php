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
    protected $appends = ['archivo_pdf_url'];
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
    public function getArchivoPdfUrlAttribute()
    {
        return $this->archivo_pdf ? asset('storage/' . $this->archivo_pdf) : null;
    }


}
