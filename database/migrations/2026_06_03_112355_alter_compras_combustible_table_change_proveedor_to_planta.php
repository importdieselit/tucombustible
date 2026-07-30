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
        Schema::table('compras_combustible', function (Blueprint $table) {
            // 1. Eliminamos la columna antigua que ya no se va a usar
            $table->dropColumn('proveedor_id');

            // 2. Creamos la nueva columna de tipo BigInt Unsigned, emparejada con 'plantas.id'
            // Se coloca justo después de la columna 'id' y añade las restricciones solicitadas
            $table->foreignId('planta_proveedor_id')
                  ->after('id')
                  ->constrained('plantas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras_combustible', function (Blueprint $table) {
            // 1. Eliminamos la relación foránea y la columna creada
            $table->dropForeign(['planta_proveedor_id']);
            $table->dropColumn('planta_proveedor_id');

            // 2. Reconstruimos la columna 'proveedor_id' tal como estaba originalmente en tu DDL
            $table->integer('proveedor_id')->after('id');
        });
    }
};