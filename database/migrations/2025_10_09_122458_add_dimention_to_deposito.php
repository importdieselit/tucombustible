<?php
// database/migrations/xxxx_add_dimensions_to_depositos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
            // Dimensiones en centímetros (cm)
            $table->float('diametro')->comment('Diámetro en centímetros (cm).');
            $table->float('longitud')->after('diametro')->comment('Longitud en centímetros (cm).');
            $table->float('capacidad_maxima')->after('longitud')->comment('Capacidad máxima real en Litros.');
        });
    }

    public function down(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropColumn(['diametro', 'longitud', 'capacidad_maxima']);
        });
    }
};