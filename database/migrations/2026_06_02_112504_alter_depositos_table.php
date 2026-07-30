<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depositos', function (Blueprint $blueprint) {
            // 1. Añadimos la relación formal con Sedes
            $blueprint->foreignId('id_sede')->nullable()->after('id')->constrained('sedes')->nullOnDelete();
            
            // 2. Añadimos la relación formal con Tipos de Combustible
            $blueprint->foreignId('tipo_combustible_id')->nullable()->after('producto')->constrained('tipos_combustible')->nullOnDelete();
            
            // 3. Añadimos el campo geométrico faltante para tanques rectangulares
            $blueprint->double('ancho', 8, 2)->nullable()->after('longitud')->comment('Ancho en centímetros (cm) para formas rectangulares.');
        });
    }

    public function down(): void
    {
        Schema::table('depositos', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['id_sede']);
            $blueprint->dropColumn('id_sede');
            $blueprint->dropForeign(['tipo_combustible_id']);
            $blueprint->dropColumn('tipo_combustible_id');
            $blueprint->dropColumn('ancho');
        });
    }
};
