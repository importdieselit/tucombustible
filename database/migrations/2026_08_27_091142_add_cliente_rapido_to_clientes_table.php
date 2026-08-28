<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Cambiar el valor por defecto del campo status a 2
            $table->integer('status')->default(2)->change();

            // Agregar la columna cliente_rapido después de status
            $table->boolean('cliente_rapido')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Revertir el valor por defecto de status al original (1)
            $table->integer('status')->default(1)->change();

            // Eliminar la columna cliente_rapido
            $table->dropColumn('cliente_rapido');
        });
    }
};