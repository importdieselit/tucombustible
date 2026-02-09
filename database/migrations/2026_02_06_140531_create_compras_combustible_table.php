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
        Schema::create('compras_combustible', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->integer('proveedor_id');
            $table->integer('cantidad_litros');
            $table->integer('cantidad_recibida')->nullable();
            $table->integer('planta_destino_id');
            $table->date('fecha');
            $table->integer('vehiculo_id')->nullable();
            $table->integer('cisterna')->nullable();
            $table->string('estatus');
            $table->integer('viaje_id')->nullable();
            $table->string('sap', 20)->nullable();
            $table->string('observaciones')->nullable();
            $table->string('tipo');
            $table->boolean('flete')->default(false);
            $table->string('otro_vehiculo', 50)->nullable();
            $table->string('otro_chofer', 50)->nullable();
            $table->string('otro_ayudante', 50)->nullable();
            $table->string('otro_proveedor', 100)->nullable();
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
        Schema::dropIfExists('compras_combustible');
    }
};
