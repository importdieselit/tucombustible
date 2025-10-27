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
        // 1. Cabecera de la Orden de Compra (OC) / Solicitud
        Schema::create('suministros_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordenes')->onDelete('cascade');
            $table->foreignId('usuario_solicitante_id')->nullable();
            $table->foreignId('usuario_aprobador_id')->nullable();
            
            // Campos de seguimiento administrativo (Admin)
            $table->string('referencia_compra')->nullable()->comment('Referencia de factura o ticket de compra');
            $table->decimal('costo_total', 10, 2)->nullable();
            $table->string('foto_factura')->nullable()->comment('Ruta al archivo de la factura');

            // 1: Solicitada (Pendiente Admin), 2: Aprobada, 3: Rechazada, 4: Usada en OT
            $table->integer('estatus')->default(1)->comment('1: Solicitada, 2: Aprobada, 3: Rechazada, 4: Usada');
            
            $table->timestamps();
        });

        // 2. Detalle de los ítems solicitados/comprados
        Schema::create('suministros_compras_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suministro_compra_id')->constrained('suministros_compras')->onDelete('cascade');
            $table->foreignId('inventario_id')->nullable()->comment('Referencia a Inventario si existe');
            
            $table->string('descripcion')->comment('Descripción del ítem (manual o de inventario)');
            $table->integer('cantidad_solicitada');
            
            $table->decimal('costo_unitario_aprobado', 10, 2)->nullable()->comment('Costo cargado por Admin');
            $table->integer('cantidad_aprobada')->nullable()->comment('Cantidad final aprobada/comprada');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suministros_compras_detalles');
        Schema::dropIfExists('suministros_compras');
    }
};
