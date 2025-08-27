<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario_suministros', function (Blueprint $table) {
            $table->bigInteger('id_inventario_suministro')->autoIncrement();
            $table->integer('estatus')->nullable();
            $table->bigInteger('id_usuario')->nullable();
            $table->bigInteger('nro_orden')->nullable();
            $table->integer('destino')->nullable();
            $table->text('servicio')->nullable();
            $table->bigInteger('id_auto')->nullable();
            $table->text('observacion')->nullable();
            $table->date('fecha_in')->nullable();
            $table->text('suministros')->nullable();
            $table->text('anulacion')->nullable();
            $table->integer('kilometraje')->nullable();
            $table->integer('postestatus')->nullable();
            $table->text('facturacion')->nullable();
            $table->bigInteger('id_emisor')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_suministros');
    }
};
