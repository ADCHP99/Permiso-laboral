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
        $operaciones = DB::table('departamentos')->where('nombre', 'Operaciones')->value('id');
        $procesos = DB::table('departamentos')->where('nombre', 'Procesos')->value('id');

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

        $gerenteSistema = Empleado::create([
            'nombre' => 'Daniel',
            'apellido' => 'Rendón',
            'cedula' => '0987654321',
            'telefono' => '04-3805401',
            'extension' => '1201',
            'celular' => '0993993957',
            'correo' => 'drendon@liris.com.ec',
            'fecha_nacimiento' => '1989-01-01',
            'cargo' => 'Gerente Área Sistemas',
            'rol' => 'Gerente de Área',
            'departamento_id' => $sistemas,
            'jefe_id' => $presidente->id
        ]);

        $jefeDesarrollo = Empleado::create([
            'nombre' => 'Kevin',
            'apellido' => 'Rosado',
            'cedula' => '0976543214',
            'telefono' => '04-3805400',
            'extension' => '11.202',
            'celular' => '0989574707',
            'correo' => 'krosado@liris.com.ec',
            'fecha_nacimiento' => '1990-03-12',
            'cargo' => 'Jefe Desarrollo',
            'rol' => 'Jefe Inmediato',
            'departamento_id' => $sistemas,
            'jefe_id' => $gerenteSistema->id
        ]);

        $jefeTecnologia = Empleado::create([
            'nombre' => 'Juan',
            'apellido' => 'Valencia',
            'cedula' => '0976543210',
            'telefono' => '04-3805400',
            'extension' => '11.203',
            'celular' => '0985182052',
            'correo' => 'jvalencia@liris.com.ec',
            'fecha_nacimiento' => '1990-03-12',
            'cargo' => 'Jefe Tecnologia',
            'rol' => 'Jefe Inmediato',
            'departamento_id' => $sistemas,
            'jefe_id' => $gerenteSistema->id
        ]);

        $jefeSoporteTecnico = Empleado::create([
            'nombre' => 'Julio',
            'apellido' => 'Martin',
            'cedula' => '0976543219',
            'telefono' => '043805400',
            'extension' => '11.201',
            'celular' => '0982988275',
            'correo' => 'jefesoportetecnico@liris.com.ec',
            'fecha_nacimiento' => '1990-03-12',
            'cargo' => 'Jefe Soporte Tecnico',
            'rol' => 'Jefe Inmediato',
            'departamento_id' => $sistemas,
            'jefe_id' => $gerenteSistema->id
        ]);

        Empleado::create([
            'nombre' => 'Roni',
            'apellido' => 'Esquit',
            'cedula' => '0954321098',
            'telefono' => '043805400',
            'extension' => '11.215',
            'celular' => '+502 4106 0279',
            'correo' => 'resquit@liris.com.ec',
            'fecha_nacimiento' => '1994-06-05',
            'cargo' => 'Especialista de Diseño y Desarrollo Funcional',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeDesarrollo->id
        ]);

        Empleado::create([
            'nombre' => 'Irving',
            'apellido' => 'Gutierrez',
            'cedula' => '0829434953',
            'telefono' => '04-3805400',
            'extension' => '11.210',
            'celular' => '+52 2411685320',
            'correo' => 'igutierrez@liris.com.ec',
            'fecha_nacimiento' => '1994-03-05',
            'cargo' => 'Ingeniero de Desarrollo',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeDesarrollo->id
        ]);
        Empleado::create([
            'nombre' => 'Jorge',
            'apellido' => 'Hidalgo',
            'cedula' => '0979723034',
            'telefono' => '043805400',
            'extension' => '11.210',
            'celular' => '0992688332',
            'correo' => 'jchidalgo@liris.com.ec',
            'fecha_nacimiento' => '1994-03-05',
            'cargo' => 'Asistente Desarrollo',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeDesarrollo->id
        ]);
        Empleado::create([
            'nombre' => 'Jorge',
            'apellido' => 'Paladines',
            'cedula' => '0923393123',
            'telefono' => '	04-3805400',
            'extension' => '11.213',
            'celular' => '0980898265',
            'correo' => 'jpaladines@liris.com.ec',
            'fecha_nacimiento' => '1995-06-12',
            'cargo' => 'Analista de Sistemas',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeDesarrollo->id
        ]);

        Empleado::create([
            'nombre' => 'Mario',
            'apellido' => 'Jalca',
            'cedula' => '0790120011',
            'telefono' => '04-3805400',
            'extension' => '',
            'celular' => '0990271353',
            'correo' => 'mjalca@liris.com.ec',
            'fecha_nacimiento' => '1990-10-12',
            'cargo' => 'Tecnico de soporte',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeSoporteTecnico->id
        ]);
        Empleado::create([
            'nombre' => 'Douglas',
            'apellido' => 'Macias',
            'cedula' => '0956984353',
            'telefono' => '04-3805400',
            'extension' => '',
            'celular' => '0939240447',
            'correo' => 'dmacias@liris.com.ec',
            'fecha_nacimiento' => '1995-06-12',
            'cargo' => 'Tecnico de soporte',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeSoporteTecnico->id
        ]);

        Empleado::create([
            'nombre' => 'Alejandro',
            'apellido' => 'Chucuyan',
            'cedula' => '0950990432',
            'telefono' => '',
            'extension' => '',
            'celular' => '0980836419',
            'correo' => 'alejandro@liris.com.ec',
            'fecha_nacimiento' => '1999-03-09',
            'cargo' => 'Ingeniero de Desarrollo en entrenamiento',
            'rol' => 'Empleado',
            'departamento_id' => $sistemas,
            'jefe_id' => $jefeDesarrollo->id
        ]);

        //RRHH
        $gerenteRrhh = Empleado::create([
            'nombre' => 'Ruth',
            'apellido' => 'Medina',
            'cedula' => '0900320193',
            'telefono' => '04-3805400',
            'extension' => '11.300',
            'celular' => '0990907825',
            'correo' => 'rjmedina@liris.com.ec',
            'fecha_nacimiento' => '1992-01-01',
            'cargo' => 'Gerente de RRHH',
            'rol' => 'Gerente de Recursos Humanos',
            'departamento_id' => $rrhh,
            'jefe_id' => $presidente->id
        ]);
        $jefeRrhh = Empleado::create([
            'nombre' => 'Patricia',
            'apellido' => 'Rivera',
            'cedula' => '0974635000',
            'telefono' => '04-3805400',
            'extension' => '11.301',
            'celular' => '0996812170',
            'correo' => 'privera@liris.com.ec',
            'fecha_nacimiento' => '1992-01-01',
            'cargo' => 'Jefe de Recursos Humanos',
            'rol' => 'Jefe Inmediato',
            'departamento_id' => $rrhh,
            'jefe_id' => $gerenteRrhh->id
        ]);
        Empleado::create([
            'nombre' => 'Lisbeth',
            'apellido' => 'Medrano',
            'cedula' => '0814475878',
            'telefono' => '04-3805400',
            'extension' => '11.308',
            'celular' => '0997020409',
            'correo' => 'lmedrano@liris.com.ec',
            'fecha_nacimiento' => '1990-11-20',
            'cargo' => 'Coordinadora de Selección',
            'rol' => 'Empleado',
            'departamento_id' =>$rrhh ,
            'jefe_id' => $jefeRrhh->id
        ]);

        Empleado::create([
            'nombre' => 'Evelyn',
            'apellido' => 'Zambrano',
            'cedula' => '0723408230',
            'telefono' => '04-3805400',
            'extension' => '11.305',
            'celular' => '0997767923',
            'correo' => 'ezambrano@liris.com.ec',
            'fecha_nacimiento' => '1990-11-20',
            'cargo' => 'Analista de Nómina',
            'rol' => 'Empleado',
            'departamento_id' =>$rrhh ,
            'jefe_id' => $jefeRrhh->id
        ]);
        Empleado::create([
            'nombre' => 'Daniela',
            'apellido' => 'Ramon',
            'cedula' => '0923365574',
            'telefono' => '04-3805400',
            'extension' => '11.302',
            'celular' => '0980107048',
            'correo' => 'dramon@liris.com.ec',
            'fecha_nacimiento' => '1995-09-15',
            'cargo' => 'Analista de Recursos Humanos Corporativo',
            'rol' => 'Empleado',
            'departamento_id' =>$rrhh ,
            'jefe_id' => $jefeRrhh->id
        ]);

        //procesos
        $GerenteProcesos= Empleado::create([
            'nombre' => 'Paola',
            'apellido' => 'Garcia',
            'cedula' => '0986854445',
            'telefono' => '04-3805400',
            'extension' => '11.500',
            'celular' => '0988833061',
            'correo' => 'pgarcia@liris.com.ec',
            'fecha_nacimiento' => '1995-09-15',
            'cargo' => 'Gerente de Procesos y Proyectos',
            'rol' => 'Gerente de Área',
            'departamento_id' =>$procesos ,
            'jefe_id' => $presidente->id
        ]);
        $JefeProcesos= Empleado::create([
            'nombre' => 'Diana',
            'apellido' => 'Brusil',
            'cedula' => '0986857866',
            'telefono' => '04-3805400',
            'extension' => '11.501',
            'celular' => '0997772042',
            'correo' => 'dbrusil@liris.com.ec',
            'fecha_nacimiento' => '1995-09-15',
            'cargo' => 'Jefe de Procesos y Proyectos',
            'rol' => 'Jefe Inmediato',
            'departamento_id' =>$procesos ,
            'jefe_id' => $GerenteProcesos->id
        ]);
    }
}

