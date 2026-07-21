<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Agregamos la marca de aliado comercial (por defecto false)
            $table->boolean('es_aliado_comercial')
                  ->default(false)
                  ->after('status')
                  ->comment('0 = Cliente normal, 1 = Aliado comercial para trasegados externos');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('es_aliado_comercial');
        });
    }
};