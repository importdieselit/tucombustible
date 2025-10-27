<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conductor_id'); // ID del conductor
            $table->unsignedBigInteger('vehiculo_id')->nullable(); // ID del vehículo (opcional)
            $table->unsignedBigInteger('pedido_id')->nullable(); // ID del pedido relacionado (opcional)
            $table->enum('tipo', ['averia', 'accidente', 'otro'])->default('otro'); // Tipo de incidencia
            $table->string('titulo', 200); // Título breve de la incidencia
            $table->text('descripcion'); // Descripción detallada
            $table->string('ubicacion', 300)->nullable(); // Ubicación donde ocurrió
            $table->decimal('latitud', 10, 8)->nullable(); // Latitud GPS
            $table->decimal('longitud', 11, 8)->nullable(); // Longitud GPS
            $table->string('foto', 500)->nullable(); // Ruta de la foto
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelto', 'cancelado'])->default('pendiente');
            $table->text('notas_admin')->nullable(); // Notas del administrador
            $table->timestamp('fecha_resolucion')->nullable(); // Fecha de resolución
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('conductor_id');
            $table->index('vehiculo_id');
            $table->index('pedido_id');
            $table->index('estado');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incidencias');
    }
}
