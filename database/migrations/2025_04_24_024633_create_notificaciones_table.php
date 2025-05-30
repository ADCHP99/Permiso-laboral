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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->unsignedBigInteger('origen_empleado_id')->nullable();
            $table->foreign('origen_empleado_id')->references('id')->on('empleados')->nullOnDelete();
            $table->string('tipo')->nullable();
            $table->string('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_notificacion')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacionse');
    }
};
