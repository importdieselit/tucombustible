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
        Schema::table('inventario_suministros', function (Blueprint $table) {
            $table->foreignId('id_auto')
                  ->constrained('vehiculos', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_emisor')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_usuario')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_suministros', function (Blueprint $table) {
            $table->dropForeign(['id_auto']);
            $table->dropForeign(['id_emisor']);
            $table->dropForeign(['id_usuario']);
        });
    }
};
