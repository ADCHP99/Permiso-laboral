<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('apellido', 60);
            $table->string('cedula', 10)->unique();
            $table->string('telefono', 15)->unique();
            $table->string('extension', 10)->nullable();
            $table->string('celular', 15)->nullable();
            $table->string('correo', 100)->unique();
            $table->date('fecha_nacimiento');
            $table->string('cargo', 100);
            $table->enum('rol', ['Empleado', 'Jefe', 'Gerente Area', 'RRHH', 'Presidente']);
            $table->string('foto_perfil')->nullable();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->unsignedBigInteger('jefe_id')->nullable();
            $table->foreign('jefe_id')->references('id')->on('empleados')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
