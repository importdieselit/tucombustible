<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chequeos_depositos_detalles', function (Blueprint $table) {
            // Creamos la columna después de id_deposito
            $table->unsignedBigInteger('id_tipos_combustible')->after('id_deposito');

            // Definimos la clave foránea explícita hacia tu tabla de tipos de combustibles
            $table->foreign('id_tipos_combustible', 'chequeos_depositos_detalles_id_tipos_combustible_foreign')
                  ->references('id')
                  ->on('tipos_combustible')
                  ->onUpdate('no action')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('chequeos_depositos_detalles', function (Blueprint $table) {
            $table->dropForeign('chequeos_depositos_detalles_id_tipos_combustible_foreign');
            $table->dropColumn('id_tipos_combustible');
        });
    }
};