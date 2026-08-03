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
        Schema::table('historial_llenados_cupos_prepagados', function (Blueprint $table) {
            $table->unsignedBigInteger('chofer_cliente_id')->after('id_deposito');
            $table->unsignedBigInteger('placa_vehiculo_id')->after('chofer_cliente_id');

            // Definición de las restricciones de clave foránea
            $table->foreign('chofer_cliente_id', 'llenados_cupos_prepagados_chofer_foreign')
                  ->references('id')
                  ->on('choferes_clientes')
                  ->onDelete('restrict')
                  ->onUpdate('no action');

            $table->foreign('placa_vehiculo_id', 'llenados_cupos_prepagados_placa_foreign')
                  ->references('id')
                  ->on('placas_vehiculos')
                  ->onDelete('restrict')
                  ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_llenados_cupos_prepagados', function (Blueprint $table) {
            // Tumbamos primero las relaciones para evitar que truene el rollback
            $table->dropForeign('llenados_cupos_prepagados_chofer_foreign');
            $table->dropForeign('llenados_cupos_prepagados_placa_foreign');

            // Removemos las columnas limpiamente
            $table->dropColumn(['chofer_cliente_id', 'placa_vehiculo_id']);
        });
    }
};