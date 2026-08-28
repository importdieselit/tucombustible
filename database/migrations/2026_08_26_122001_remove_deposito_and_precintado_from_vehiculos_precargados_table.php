<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->dropForeign(['id_deposito']);
            $table->dropColumn(['id_deposito', 'esta_precintado']);
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->foreignId('id_deposito')->nullable()->after('id_sede')->constrained('depositos');
            $table->boolean('esta_precintado')->default(false)->after('id_usuario');
        });
    }
};