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
        Schema::create('vehiculos_precargados', function (Blueprint $table) {
            $table->id(); // id de la tabla
            $table->unsignedBigInteger('id_vehiculo')->nullable(); // FK a la tabla de vehículos
            $table->double('cantidad_cargada', 8, 2); // Cantidad cargada, con 2 decimales
            $table->timestamp('fecha_hora_carga'); // Fecha y hora de la carga
            $table->tinyInteger('estatus')->default(0)->comment('0 = cargada, 1 = despachada'); // Estatus de la carga
            $table->timestamp('fecha_hora_despacho')->nullable(); // Fecha y hora de despacho (puede ser nula)
            $table->string('tipo_producto', 1); // Tipo de producto (I, M, G, A)
            $table->timestamps(); // created_at y updated_at

            // Definición de la clave foránea
            $table->foreign('id_vehiculo')->references('id')->on('vehiculos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehiculos_precargados');
    }
};
