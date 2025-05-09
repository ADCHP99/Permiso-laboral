<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aprobacion extends Model
{
    protected $fillable = [
        'solicitud_id', 'aprobador_id', 'nivel', 'resultado', 'observacion', 'fecha_aprobacion'
    ];
    protected $table = 'aprobaciones';
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function aprobador()
    {
        return $this->belongsTo(Empleado::class, 'aprobador_id');
    }
}
