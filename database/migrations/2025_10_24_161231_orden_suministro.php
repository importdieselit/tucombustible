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
        // Tabla de detalle que contendrá los ítems de suministro por cada orden
        Schema::create('orden_suministros', function (Blueprint $table) {
            $table->id();

            // Relación con la tabla principal (OrdenSuministro)
            $table->foreignId('orden_id')
                  ->constrained('ordenes')
                  ->onDelete('cascade') // Si se borra la orden, se borran sus detalles
                  ->comment('ID de la orden a la que pertenece el detalle');

            // Referencia al ítem de inventario (asumiendo una tabla 'inventario')
            $table->integer('inventario_id')->unsigned()->nullable()
                  ->comment('ID del ítem de inventario (si aplica)');

            $table->unsignedInteger('cantidad_solicitada')
                  ->comment('Cantidad del suministro solicitada');
            $table->boolean('es_manual')
                  ->default(false)
                  ->comment('Indica si el suministro fue agregado manualmente');
            $table->string('descripcion')
                  ->nullable()
                  ->comment('Descripción del suministro (si es manual)');

            $table->timestamps();

            // Asegurar que no se repita el mismo suministro en la misma orden
            $table->unique(['orden_id', 'inventario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_suministros');
    }
};
