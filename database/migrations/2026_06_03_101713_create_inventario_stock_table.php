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
            $table->foreignId('inventario_id')->constrained('inventario');
            $table->foreignId('ubicacion_id')->constrained('ubicaciones');
            
            $table->decimal('cantidad_actual', 12, 4); // Soporta litros, kilos o unidades (Sin redondeo)
            $table->decimal('cantidad_reservada', 12, 4)->default(0); // Material apartado para una Orden de Trabajo
            
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
