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
        // Esta tabla guarda las planificaciones futuras que generarán una Orden de Mantenimiento
        Schema::create('mantenimientos_programados', function (Blueprint $table) {
            $table->id();
            
            // Relación con el vehículo a mantener
            $table->foreignId('vehiculo_id')
                  ->constrained('vehiculos')
                  ->onDelete('cascade')
                  ->comment('ID del vehículo planificado para mantenimiento.');

            $table->foreignId('creado_por_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que planificó el mantenimiento.');

            $table->date('fecha_programada')
                  ->comment('Día específico en que el mantenimiento debe realizarse.');

            // Tipo de mantenimiento (M1, M2, M3, M4, etc.)
            $table->string('tipo_mantenimiento', 50)
                  ->comment('Tipo de mantenimiento (ej: M1, M2, Servicio de 10k, etc.).');
            
            $table->text('descripcion_plan')->nullable()
                  ->comment('Descripción breve de las tareas a realizar.');

            // ID de la Orden de Trabajo generada (Será NULL hasta que se genere la OT)
            $table->foreignId('orden_id')
                  ->nullable()
                  ->constrained('ordenes')
                  ->onDelete('set null')
                  ->comment('Referencia a la Orden de Trabajo generada a partir de esta planificación.');

            // Estado de la planificación: 1: Programado, 2: OT Generada, 3: Cancelado
            $table->integer('estatus')->default(1)
                  ->comment('1: Programado, 2: OT Generada, 3: Cancelado.');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos_programados');
    }
};
