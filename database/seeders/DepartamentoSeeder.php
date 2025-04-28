<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departamentos')->insert([
            ['nombre' => 'Sistemas'],
            ['nombre' => 'Recursos Humanos'],
            ['nombre' => 'Finanzas'],
            ['nombre' => 'Presidencia'],
        ]);
    }
}
