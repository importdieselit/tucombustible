<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abastecimientos_tanques', function (Blueprint $table) {
            // Permitir id_vehiculo nulo (ya que el origen puede ser una Compra sin vehículo interno)
            $table->bigInteger('id_vehiculo')->nullable()->change();

            // Clave foránea para relacionar con Compras de Combustible
            $table->foreignId('id_compra_combustible')
                ->nullable()
                ->after('id_precarga_origen')
                ->constrained('compras_combustible')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abastecimientos_tanques', function (Blueprint $table) {
            $table->dropForeign(['id_compra_combustible']);
            $table->dropColumn('id_compra_combustible');
            $table->bigInteger('id_vehiculo')->nullable(false)->change();
        });
    }
};