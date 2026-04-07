<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('nro_venta')->unique(); // Correlativo interno
            $table->string('nro_profit')->nullable();  // Referencia externa
            $table->foreignId('id_cliente');
            $table->foreignId('id_almacen')->constrained('almacenes'); // El filtro que pediste
            $table->date('fecha');
            $table->decimal('total_venta');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('id_inventario');
            $table->integer('cantidad');
            $table->decimal('precio_unitario');
            $table->decimal('subtotal');
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
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('venta_detalles');
    }
}
