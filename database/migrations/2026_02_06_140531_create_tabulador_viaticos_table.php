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
        Schema::create('tabulador_viaticos', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('destino');
            $table->string('tipo_viaje')->nullable();
            $table->decimal('pago_chofer_ejecutivo')->default(0);
            $table->decimal('pago_chofer')->default(0);
            $table->decimal('pago_ayudante')->default(0);
            $table->integer('peajes')->default(0);
            $table->decimal('viatico_desayuno')->default(0);
            $table->decimal('viatico_almuerzo')->default(0);
            $table->decimal('viatico_cena')->default(0);
            $table->decimal('costo_pernocta')->default(0);
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
        Schema::dropIfExists('tabulador_viaticos');
    }
};
