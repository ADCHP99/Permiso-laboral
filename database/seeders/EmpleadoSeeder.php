<?php

namespace Database\Seeders;

use App\Models\Empleado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpleadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sistemas = DB::table('departamentos')->where('nombre', 'Sistemas')->value('id');
        $rrhh = DB::table('departamentos')->where('nombre', 'Recursos Humanos')->value('id');
        $presidencia = DB::table('departamentos')->where('nombre', 'Presidencia')->value('id');

        $presidente = Empleado::create([
            'nombre' => 'Antonio',
            'apellido' => 'Saab',
            'cedula' => '0990345678',
            'telefono' => '04-3805400',
            'extension' => '1200',
            'celular' => '0999000111',
            'correo' => 'ajsaab@empresa.com',
            'fecha_nacimiento' => '1980-07-30',
            'cargo' => 'Presidente',
            'rol' => 'Presidente',
            'departamento_id' => $presidencia,
            'jefe_id' => null
        ]);

        $gerente = Empleado::create([
            'nombre' => 'Daniel',
            'apellido' => 'Rendón',
            'cedula' => '0987654321',
            'telefono' => '04-3805401',
            'extension' => '1201',
            'celular' => '0988000222',
            'correo' => 'drendon@empresa.com',
            'fecha_nacimiento' => '1989-01-01',
            'cargo' => 'Gerente Área Sistemas',
            'rol' => 'Gerente Area',
            'departamento_id' => $sistemas,
            'jefe_id' => $presidente->id
        ]);

        $jefe = Empleado::create([
            'nombre' => 'José',
            'apellido' => 'Pérez',
            'cedula' => '0976543210',
            'telefono' => '04-3805402',
            'extension' => '1202',
            'celular' => '0977000333',
            'correo' => 'jperez@empresa.com',
            'fecha_nacimiento' => '1990-03-12',
            'cargo' => 'Jefe Desarrollo',
            'rol' => 'Jefe',
            'departamento_id' => $sistemas,
            'jefe_id' => $gerente->id
        ]);

        Empleado::create([
            'nombre' => 'Carlos',
            'apellido' => 'López',
            'cedula' => '0954321098',
            'telefono' => '04-3805404',
            'extension' => '1203',
            'celular' => '0955000444',
            'correo' => 'clopez@empresa.com',
            'fecha_nacimiento' => '1994-06-05',
            'cargo' => 'Desarrollador Senior',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefe->id
        ]);
    }
}
