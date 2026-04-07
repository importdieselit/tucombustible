<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGascoCuposMensualesTable extends Migration
{
    public function up()
    {
        Schema::create('gasco_cupos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('litros_autorizados', 12, 2);
            $table->decimal('litros_consumidos', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            // Índice para búsquedas rápidas por periodo
            $table->index(['cliente_id', 'mes', 'anio']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('gasco_cupos_mensuales');
    }
}