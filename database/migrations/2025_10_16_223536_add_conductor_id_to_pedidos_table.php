<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConductorIdToPedidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Agregar chofer_id para asignar pedidos directamente al chofer
            $table->unsignedBigInteger('chofer_id')->nullable()->after('vehiculo_id');
            
            // Foreign key con choferes
            $table->foreign('chofer_id')
                  ->references('id')
                  ->on('choferes')
                  ->onDelete('set null');
            
            // Índice para mejorar búsquedas por chofer
            $table->index('chofer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Eliminar foreign key e índice
            $table->dropForeign(['chofer_id']);
            $table->dropIndex(['chofer_id']);
            
            // Eliminar columna
            $table->dropColumn('chofer_id');
        });
    }
}
