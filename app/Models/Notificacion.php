<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $fillable = [
        'empleado_id', 'origen_empleado_id', 'mensaje', 'leida', 'fecha_notificacion'
    ];

    public function receptor()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function origen()
    {
        return $this->belongsTo(Empleado::class, 'origen_empleado_id');
    }
}
