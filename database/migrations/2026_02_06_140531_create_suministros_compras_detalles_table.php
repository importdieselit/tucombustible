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
        Schema::create('suministros_compras_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('suministro_compra_id');
            $table->unsignedBigInteger('inventario_id')->nullable()->comment('Referencia a Inventario si existe');
            $table->string('descripcion')->comment('Descripción del ítem (manual o de inventario)');
            $table->integer('cantidad_solicitada');
            $table->decimal('costo_unitario_aprobado', 10)->nullable()->comment('Costo cargado por Admin');
            $table->integer('cantidad_aprobada')->nullable()->comment('Cantidad final aprobada/comprada');
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
        Schema::dropIfExists('suministros_compras_detalles');
    }
};
