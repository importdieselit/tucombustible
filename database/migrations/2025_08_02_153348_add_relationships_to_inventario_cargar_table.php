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
        Schema::table('inventario_cargar', function (Blueprint $table) {
            $table->foreignId('emisor')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_usuario')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            $table->foreignId('proveedor')
                  ->constrained('proveedores', 'id')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_cargar', function (Blueprint $table) {
            $table->dropForeign(['emisor']);
            $table->dropForeign(['id_inventario']);
            $table->dropForeign(['id_usuario']);
            $table->dropForeign(['proveedor']);
        });
    }
};
