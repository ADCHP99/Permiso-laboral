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
        Schema::create('aprobaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('aprobador_id')->constrained('empleados')->cascadeOnDelete();
            $table->enum('nivel', ['jefe', 'gerente_area', 'rrhh', 'presidencia']);
            $table->enum('resultado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprobaciones');
    }
};
