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
        Schema::table('depositos', function (Blueprint $table) {
            $table->tinyInteger('llena_cupo_prepagado')
                ->default(0)
                ->comment('1 = Habilitado para la modalidad de Llenado con Cupo Prepagado, 0 = No habilitado')
                ->after('rotacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depositos', function (Blueprint $table) {
            $table->dropColumn('llena_cupo_prepagado');
        });
    }
};