<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacen_estructuras_grid', function (Blueprint $table) {
            // Almacena la matriz de la forma: {"1": [1,2,3], "2": [1,2], "3": [1]}
            $table->json('config_layout')->nullable()->after('cantidad_secciones');
        });
    }

    public function down(): void
    {
        Schema::table('almacen_estructuras_grid', function (Blueprint $table) {
            $table->dropColumn('config_layout');
        });
    }
};
