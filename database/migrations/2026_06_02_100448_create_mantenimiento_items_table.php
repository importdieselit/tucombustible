<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMantenimientoItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mantenimiento_items', function (Blueprint $table) {
            $table->id();
            $table->string('categoria'); // 'OVERHAUL' o 'REPARACION GENERAL'
            $table->string('nombre');    // Ej: 'MOTOR', 'CAJA', 'ELECTRICIDAD'
            // Guardamos con alta precisión decimal para finanzas
            $table->decimal('costo_promedio', 12, 2)->default(0.00);
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
        Schema::dropIfExists('mantenimiento_items');
    }
}
