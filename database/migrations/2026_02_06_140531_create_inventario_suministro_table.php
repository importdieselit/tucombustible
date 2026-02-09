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
        Schema::create('inventario_suministro', function (Blueprint $table) {
            $table->unsignedBigInteger('id_inventario_suministro');
            $table->integer('estatus')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->bigInteger('id_orden')->nullable();
            $table->integer('destino')->nullable();
            $table->text('servicio')->nullable();
            $table->unsignedBigInteger('id_auto')->nullable();
            $table->date('fecha_in')->nullable();
            $table->bigInteger('id_inventario')->nullable();
            $table->text('anulacion')->nullable();
            $table->integer('kilometraje')->nullable();
            $table->unsignedBigInteger('id_emisor')->nullable();
            $table->timestamps();
            $table->integer('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_suministro');
    }
};
