<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Viajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('viajes', function (Blueprint $table) {
            $table->id();
            $table->string('destino_ciudad'); // Clave de búsqueda en el tabulador
            $table->unsignedBigInteger('chofer_id'); // Usuario asignado
            $table->integer('ayudante')->default(0);
            $table->integer('custodia_count')->default(0);
            $table->datetime('fecha_salida')->default(1); // Para calcular viáticos de comida/pernocta
            $table->string('status')->default('PENDIENTE_VIATICOS');

            $table->foreign('chofer_id')->references('id')->on('choferes');
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
         Schema::dropIfExists('viajes');
    }
}
