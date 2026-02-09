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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('conductor_id');
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->enum('tipo', ['averia', 'accidente', 'otro'])->default('otro');
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->string('ubicacion', 300)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('foto', 500)->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'resuelto', 'cancelado'])->default('pendiente');
            $table->text('notas_admin')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
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
        Schema::dropIfExists('incidencias');
    }
};
