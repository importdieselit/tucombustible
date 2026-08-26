<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('inventario_stock', function (Blueprint $table) {
            $table->id();
            
            // BIGINT UNSIGNED para coincidir con inventario.id
            $table->foreignId('inventario_id')->constrained('inventario');
            
            // Si la tabla ubicaciones usa el id standard de Laravel (BIGINT UNSIGNED)
            $table->foreignId('ubicacion_id')->constrained('ubicaciones');
            
            $table->decimal('cantidad_actual', 12, 4);
            $table->decimal('cantidad_reservada', 12, 4)->default(0);
            
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            
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
        Schema::dropIfExists('inventario_stock');
    }
}