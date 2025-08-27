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
        Schema::table('historial_mantenimiento', function (Blueprint $table) {
            $table->foreignId('id_servicio')
                  ->constrained('servicios', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_vehiculo')
                  ->constrained('vehiculos', 'id')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_mantenimiento', function (Blueprint $table) {
            $table->dropForeign(['id_servicio']);
            $table->dropForeign(['id_vehiculo']);
        });
    }
};
