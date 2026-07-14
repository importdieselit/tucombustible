<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chequeos_depositos_detalles', function (Blueprint $table) {
            // Lo que el sistema decía que debía haber según el Ledger antes de meter la vara
            $table->decimal('litros_teoricos', 12, 2)->nullable()->after('litros_calculados');
            // La diferencia: litros_calculados (real) - litros_teoricos (sistema)
            $table->decimal('merma_calculada', 12, 2)->nullable()->after('litros_teoricos');
        });
    }

    public function down(): void
    {
        Schema::table('chequeos_depositos_detalles', function (Blueprint $table) {
            $table->dropColumn(['litros_teoricos', 'merma_calculada']);
        });
    }
};