<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'nombre', 'apellido', 'cedula', 'telefono', 'extension', 'celular', 'correo',
        'fecha_nacimiento', 'cargo', 'rol', 'foto_perfil', 'departamento_id', 'jefe_id'
    ];
    public function departamento() {
        return $this->belongsTo(Departamento::class);
    }

    public function jefe() {
        return $this->belongsTo(Empleado::class, 'jefe_id');
    }

    public function subordinados() {
        return $this->hasMany(Empleado::class, 'jefe_id');
    }
}
