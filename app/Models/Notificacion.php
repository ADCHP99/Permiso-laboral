<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $fillable = [
        'empleado_id', 'origen_empleado_id','tipo', 'mensaje', 'leida', 'fecha_notificacion'
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_notificacion' => 'datetime',
    ];
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function origen()
    {
        return $this->belongsTo(Empleado::class, 'origen_empleado_id');
    }
}
