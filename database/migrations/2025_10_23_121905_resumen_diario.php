<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resumen_diario', function (Blueprint $table) {
            $table->id('id_resumen'); // Campo id_resumen
            $table->date('fecha'); // La fecha es única para el registro diario
            
            // Mantenimientos
            $table->unsignedInteger('plan')->default(0)->comment('Mantenimientos planificados según KM/tiempo.');
            $table->unsignedInteger('real')->default(0)->comment('Mantenimientos realizados durante el día.');
            $table->json('plan_models')->nullable()->comment('JSON de modelos y cantidad planificada para mantenimiento.');
            
            // KPIs de Eficiencia
            $table->decimal('disponibilidad', 5, 2)->default(0.00)->comment('Porcentaje de disponibilidad de la flota (Eficiencia).');
            $table->decimal('conteo', 5, 2)->default(0.00)->comment('Efectividad del conteo de almacén (porcentaje).');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resumen_diario');
    }
};
