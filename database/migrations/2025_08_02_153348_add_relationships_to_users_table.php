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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_master')
                  ->constrained('users', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_perfil')
                  ->constrained('perfiles', 'id')
                  ->onDelete('cascade');
            $table->foreignId('id_persona')
                  ->constrained('personas', 'id')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_master']);
            $table->dropForeign(['id_perfil']);
            $table->dropForeign(['id_persona']);
        });
    }
};
