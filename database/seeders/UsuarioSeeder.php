<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empleados = Empleado::all();

        foreach ($empleados as $empleado) {
            Usuario::create([
                'nombre_usuario' => $empleado->cedula, // igual a cédula
                'password' => Hash::make('password'), // clave por defecto
                'empleado_id' => $empleado->id
            ]);
        }
    }
}
