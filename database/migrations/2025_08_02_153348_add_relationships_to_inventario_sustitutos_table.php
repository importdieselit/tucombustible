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
        Schema::table('inventario_sustitutos', function (Blueprint $table) {
            $table->foreignId('id_inventario')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r10')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r1')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r2')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r3')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r4')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r5')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r6')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r7')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r8')
                  ->constrained('inventario', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_inventario_r9')
                  ->constrained('inventario', 'id')
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
        Schema::table('inventario_sustitutos', function (Blueprint $table) {
            $table->dropForeign(['id_inventario']);
            $table->dropForeign(['id_inventario_r10']);
            $table->dropForeign(['id_inventario_r1']);
            $table->dropForeign(['id_inventario_r2']);
            $table->dropForeign(['id_inventario_r3']);
            $table->dropForeign(['id_inventario_r4']);
            $table->dropForeign(['id_inventario_r5']);
            $table->dropForeign(['id_inventario_r6']);
            $table->dropForeign(['id_inventario_r7']);
            $table->dropForeign(['id_inventario_r8']);
            $table->dropForeign(['id_inventario_r9']);
            $table->dropForeign(['id_usuario']);
        });
    }
};
