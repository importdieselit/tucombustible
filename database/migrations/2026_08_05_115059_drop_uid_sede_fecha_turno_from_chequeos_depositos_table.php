<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chequeos_depositos', function (Blueprint $table) {
            // 1. Creamos un índice normal en id_sede para respaldar la Foreign Key
            $table->index('id_sede');

            // 2. Eliminamos el índice único compuesto
            $table->dropUnique('uid_sede_fecha_turno');
        });
    }

    public function down(): void
    {
        Schema::table('chequeos_depositos', function (Blueprint $table) {
            // Revertimos el proceso en caso de rollback
            $table->unique(['id_sede', 'fecha', 'turno'], 'uid_sede_fecha_turno');
            $table->dropIndex(['id_sede']);
        });
    }
};