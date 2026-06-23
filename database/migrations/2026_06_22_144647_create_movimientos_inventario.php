<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovimientosInventario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventario');
            $table->bigInteger('ubicacion_id')->nullable(); // Tu estructura de ubicaciones
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'AJUSTE']);
            $table->decimal('cantidad', 12, 2); // El cambio (Ej: +10 o -5)
            $table->decimal('stock_anterior', 12, 2);
            $table->decimal('stock_nuevo', 12, 2);
            $table->string('motivo')->nullable(); // Para los ajustes o notas de entrada
            $table->bigInteger('accion_id')->nullable(); // Nulo si es ajuste/salida
            $table->foreignId('user_id')->constrained('users'); // Quién lo hizo
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
        Schema::dropIfExists('movimientos_inventario');
    }
}
