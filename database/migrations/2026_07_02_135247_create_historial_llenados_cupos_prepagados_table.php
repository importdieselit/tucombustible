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
        // Eliminamos la tabla obsoleta primero para limpiar el esquema
        Schema::dropIfExists('cupos_prepagados');

        // Creamos la nueva tabla con la estructura exacta de tu DDL
        Schema::create('historial_llenados_cupos_prepagados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('id_sede');
            $table->unsignedBigInteger('id_deposito')->comment('Tanque del cual se extrae el combustible');
            $table->unsignedBigInteger('tipo_combustible_id')->comment('Tipo de combustible inferido al momento del llenado');
            $table->decimal('litros', 11, 2)->comment('Cantidad de litros despachados al vehículo');
            $table->timestamps();

            // Claves foráneas utilizando los nombres de constraints exactos de tu diseño
            $table->foreign('cliente_id', 'llenados_cupos_prepagados_cliente_foreign')
                ->references('id')->on('clientes')
                ->onUpdate('no action')->onDelete('cascade');

            $table->foreign('id_sede', 'llenados_cupos_prepagados_sede_foreign')
                ->references('id')->on('sedes')
                ->onUpdate('no action')->onDelete('restrict');

            $table->foreign('id_deposito', 'llenados_cupos_prepagados_deposito_foreign')
                ->references('id')->on('depositos')
                ->onUpdate('no action')->onDelete('restrict');

            $table->foreign('tipo_combustible_id', 'llenados_cupos_prepagados_combustible_foreign')
                ->references('id')->on('tipos_combustible')
                ->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_llenados_cupos_prepagados');
        
        // NOTA: Si necesitas revertir por completo, tendrías que volver a crear 
        // la estructura de `cupos_prepagados` aquí si fuera necesario restaurarla.
    }
};