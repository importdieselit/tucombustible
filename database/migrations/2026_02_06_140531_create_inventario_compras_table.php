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
        Schema::create('inventario_compras', function (Blueprint $table) {
            $table->unsignedBigInteger('id_inventario_compra');
            $table->integer('estatus');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->bigInteger('nro_orden');
            $table->integer('destino');
            $table->unsignedBigInteger('id_auto')->nullable();
            $table->unsignedBigInteger('id_proveedor')->nullable();
            $table->text('observacion');
            $table->string('fecha_in', 14);
            $table->longText('compra');
            $table->text('anulacion');
            $table->unsignedBigInteger('id_emisor')->nullable();
            $table->string('tipo', 4)->default('old')->comment('old. new');
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
        Schema::dropIfExists('inventario_compras');
    }
};
