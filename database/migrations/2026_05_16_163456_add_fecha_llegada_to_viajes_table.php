<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('viajes', function (Blueprint $table) {
            $table->timestamp('fecha_llegada')->nullable()->after('fecha_salida');
            $table->timestamp('fecha_salida_real')->nullable()->after('fecha_salida');
        });
    }

    public function down() {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn('fecha_llegada');
            $table->dropColumn('fecha_salida_real');
        });
    }
};