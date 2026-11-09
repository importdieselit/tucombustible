<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_bunkering_detalles', function (Blueprint $table) {
            $table->decimal('toneladas_metricas', 12, 3)->after('tipo_producto');
            $table->decimal('volumen_litros', 14, 2)->after('toneladas_metricas');
            $table->decimal('precio_unitario_tm', 10, 2)->after('volumen_litros');
            $table->decimal('subtotal_producto', 14, 2)->after('precio_unitario_tm');
            $table->decimal('costo_delivery', 14, 2)->nullable()->after('subtotal_producto');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_bunkering_detalles', function (Blueprint $table) {$table->dropColumn(['toneladas_metricas', 'volumen_litros', 'precio_unitario_tm', 'subtotal_producto', 'delivery_cost']);
        });
    }
};