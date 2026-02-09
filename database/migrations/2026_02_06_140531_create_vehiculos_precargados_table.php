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
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_vehiculo')->nullable();
            $table->double('cantidad_cargada', 8, 2);
            $table->timestamp('fecha_hora_carga')->useCurrentOnUpdate()->useCurrent();
            $table->tinyInteger('estatus')->default(0)->comment('0 = cargada, 1 = despachada');
            $table->timestamp('fecha_hora_despacho')->nullable();
            $table->string('tipo_producto', 1);
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
        Schema::dropIfExists('vehiculos_precargados');
    }
};
