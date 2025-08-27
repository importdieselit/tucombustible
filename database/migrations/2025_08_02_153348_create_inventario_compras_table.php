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
        Schema::create('inventario_compras', function (Blueprint $table) {
            $table->bigInteger('id_inventario_compra')->autoIncrement();
            $table->integer('estatus');
            $table->bigInteger('id_usuario')->nullable();
            $table->bigInteger('nro_orden');
            $table->integer('destino');
            $table->bigInteger('id_auto')->nullable();
            $table->bigInteger('id_proveedor')->nullable();
            $table->text('observacion');
            $table->string('fecha_in');
            $table->text('compra');
            $table->text('anulacion');
            $table->bigInteger('id_emisor')->nullable();
            $table->string('tipo')->default('old');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_compras');
    }
};
