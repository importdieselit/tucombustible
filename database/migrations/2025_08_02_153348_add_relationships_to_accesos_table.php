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
        Schema::table('accesos', function (Blueprint $table) {
            $table->foreignId('id_modulo')
                  ->constrained('modulos', 'id')
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
        Schema::table('accesos', function (Blueprint $table) {
            $table->dropForeign(['id_modulo']);
            $table->dropForeign(['id_usuario']);
        });
    }
};
