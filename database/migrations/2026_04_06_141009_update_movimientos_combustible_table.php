<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateMovimientosCombustibleTable extends Migration
{
    public function up()
    {
        // 1. Actualización del ENUM para soportar los nuevos tipos de movimientos operativos
        DB::statement("ALTER TABLE movimientos_combustible MODIFY COLUMN tipo_movimiento 
            ENUM('entrada', 'salida', 'precarga', 'ajuste', 'recarga_prepago', 'abastecimiento', 'trasegado', 'merma', 'consumo_operativo', 'despacho') 
            NOT NULL COMMENT 'Tipo de movimiento operativo'");

        Schema::table('movimientos_combustible', function (Blueprint $table) {
            // 2. Agregar tipo_combustible_id al inicio (después de id)
            if (!Schema::hasColumn('movimientos_combustible', 'tipo_combustible_id')) {
                $table->unsignedBigInteger('tipo_combustible_id')->nullable()->after('id');
                $table->foreign('tipo_combustible_id', 'movimientos_combustible_tipo_combustible_id_foreign')
                      ->references('id')->on('tipos_combustible');
            }

            // 3. Agregar referencias de Pedido y Viaje (después de cliente_id)
            if (!Schema::hasColumn('movimientos_combustible', 'pedido_id')) {
                $table->unsignedBigInteger('pedido_id')->nullable()->after('cliente_id');
            }
            if (!Schema::hasColumn('movimientos_combustible', 'viaje_id')) {
                $table->unsignedBigInteger('viaje_id')->nullable()->after('pedido_id');
            }

            // 4. Agregar nro_ticket para coincidir con el $fillable del Modelo
            if (!Schema::hasColumn('movimientos_combustible', 'nro_ticket')) {
                $table->string('nro_ticket', 50)->nullable()->after('cant_final');
            }
        });
    }

    public function down()
    {
        Schema::table('movimientos_combustible', function (Blueprint $table) {
            $table->dropForeign('movimientos_combustible_tipo_combustible_id_foreign');
            $table->dropColumn([
                'tipo_combustible_id', 
                'pedido_id', 
                'viaje_id', 
                'nro_ticket'
            ]);
        });
        
        // Revertir el ENUM a su estado original si es necesario
        DB::statement("ALTER TABLE movimientos_combustible MODIFY COLUMN tipo_movimiento 
            ENUM('entrada', 'salida', 'precarga', 'ajuste', 'recarga_prepago') 
            NOT NULL");
    }
}