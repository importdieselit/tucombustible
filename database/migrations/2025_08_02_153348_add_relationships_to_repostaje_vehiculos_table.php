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
        Schema::table('repostaje_vehiculos', function (Blueprint $table) {
            $table->foreignId('id_admin')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_tanque')
                  ->constrained('tanques', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_us')
                  ->constrained('users', 'id')
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
        Schema::table('repostaje_vehiculos', function (Blueprint $table) {
            $table->dropForeign(['id_admin']);
            $table->dropForeign(['id_tanque']);
            $table->dropForeign(['id_us']);
            $table->dropForeign(['id_vehiculo']);
        });
    }
};
