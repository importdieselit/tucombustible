<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('contacto_alt', 255)->nullable()
                  ->after('telefono')
                  ->comment('Nombre de la persona de contacto alternativa');
            $table->string('telefono_alt', 11)->nullable()
                  ->after('contacto_alt')
                  ->comment('Teléfono de la persona de contacto alternativa');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['contacto_alt', 'telefono_alt']);
        });
    }
};
