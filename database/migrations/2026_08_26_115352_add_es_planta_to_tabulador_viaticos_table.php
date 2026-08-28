<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabulador_viaticos', function (Blueprint $table) {
            $table->boolean('es_planta')->default(false)->after('tipo_viaje');
        });

        // Asignamos es_planta = true a los IDs correspondientes a Plantas
        DB::table('tabulador_viaticos')
            ->whereIn('id', [1, 2, 3, 4, 5, 22, 24])
            ->update(['es_planta' => true]);
    }

    public function down(): void
    {
        Schema::table('tabulador_viaticos', function (Blueprint $table) {
            $table->dropColumn('es_planta');
        });
    }
};