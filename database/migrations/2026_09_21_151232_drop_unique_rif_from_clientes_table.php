<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Elimina el índice único usando el nombre del índice
            $table->dropUnique('clientes_rif_unique');
            
            // O pasa el arreglo con el nombre del campo si usaste convenciones por defecto:
            // $table->dropUnique(['rif']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unique('rif');
        });
    }
};