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
        Schema::create('movimientos_combustible', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'precarga', 'ajuste', 'recarga_prepago'])->comment('Tipo de movimiento: recarga (entrada) o despacho (salida)');
            $table->unsignedBigInteger('deposito_id');
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->integer('cantidad_litros')->comment('Cantidad de combustible movido');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->integer('vehiculo_id')->nullable();
            $table->integer('cisterna_id')->nullable();
            $table->float('cant_inicial', 10)->nullable();
            $table->float('cant_final', 10)->nullable();
            $table->string('otro_cliente', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movimientos_combustible');
    }
};
