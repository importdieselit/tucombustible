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
        Schema::create('viajes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('destino_ciudad');
            $table->unsignedBigInteger('chofer_id')->nullable();
            $table->integer('ayudante')->nullable()->default(0);
            $table->bigInteger('vehiculo_id')->nullable();
            $table->string('otro_vehiculo', 50)->nullable();
            $table->string('otro_chofer', 50)->nullable();
            $table->string('otro_ayudante', 50)->nullable();
            $table->integer('custodia_count')->default(0);
            $table->dateTime('fecha_salida')->nullable();
            $table->string('status')->default('PENDIENTE_VIATICOS');
            $table->bigInteger('cliente_id')->nullable();
            $table->string('otro_cliente', 100)->nullable();
            $table->float('litros', 10, 0)->nullable();
            $table->boolean('has_viatico')->default(false);
            $table->timestamps();
            $table->integer('usuario_id')->nullable();
            $table->text('observacion')->nullable();
            $table->integer('tipo')->nullable();
            $table->string('cisterna', 100)->nullable();
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
};
