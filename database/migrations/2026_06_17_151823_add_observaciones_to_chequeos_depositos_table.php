<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chequeos_depositos', function (Blueprint $table) {
            // Añade observaciones como nullable después del campo turno
            $table->text('observaciones')->nullable()->after('turno');
        });
    }

    public function down(): void
    {
        Schema::table('chequeos_depositos', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};