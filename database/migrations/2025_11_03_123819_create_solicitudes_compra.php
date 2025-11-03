<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesCompra extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('compra_combustible', function (Blueprint $table) {
            $table->id();
            $table->integer('proveedor_id');
            $table->integer('deposito_id');
            $table->integer('cantidad_litros');
            $table->integer('planta_destino_id');
            $table->date('fecha');
            $table->integer('vehiculo_id');
            $table->integer('cisterna');
            $table->string('estatus'); // Ej: PROGRAMADA, ASIGNADA, COMPRADA, COMPLETADA, CANCELADA
            $table->integer('viaje_id'); // Enlace a la planificación de viaje de entrega/carga
            $table->string('observaciones');
            $table->string('tipo');
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
        Schema::dropIfExists('compra_combustible');
    }
}
