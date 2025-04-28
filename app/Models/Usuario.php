<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre_usuario', 'password', 'empleado_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function getRolAttribute()
    {
        return $this->empleado?->rol;
    }
}
