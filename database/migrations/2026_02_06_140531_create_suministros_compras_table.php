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
        Schema::create('suministros_compras', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('orden_id');
            $table->unsignedBigInteger('usuario_solicitante_id')->nullable();
            $table->unsignedBigInteger('usuario_aprobador_id')->nullable();
            $table->string('referencia_compra')->nullable()->comment('Referencia de factura o ticket de compra');
            $table->decimal('costo_total', 10)->nullable();
            $table->string('foto_factura')->nullable()->comment('Ruta al archivo de la factura');
            $table->integer('estatus')->default(1)->comment('1: Solicitada, 2: Aprobada, 3: Rechazada, 4: Recibido');
            $table->string('observacion')->nullable();
            $table->string('observacion_admin')->nullable();
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
        Schema::dropIfExists('suministros_compras');
    }
};
