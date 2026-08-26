<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('estatus');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos_precargados', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};