<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campos de montos principales en compras_combustible
        Schema::table('compras_combustible', function (Blueprint $table) {
            $table->decimal('monto_usd', 12, 2)->nullable()->after('usuario_id');
            $table->decimal('monto_bs', 16, 2)->nullable()->after('monto_usd');
        });

        // 2. Trazabilidad de montos en historial_facturas_compras
        Schema::table('historial_facturas_compras', function (Blueprint $table) {
            $table->decimal('monto_usd_anterior', 12, 2)->nullable()->after('factura_path_nuevo');
            $table->decimal('monto_usd_nuevo', 12, 2)->after('monto_usd_anterior');
            $table->decimal('monto_bs_anterior', 16, 2)->nullable()->after('monto_usd_nuevo');
            $table->decimal('monto_bs_nuevo', 16, 2)->after('monto_bs_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('compras_combustible', function (Blueprint $table) {
            $table->dropColumn(['monto_usd', 'monto_bs']);
        });

        Schema::table('historial_facturas_compras', function (Blueprint $table) {
            $table->dropColumn(['monto_usd_anterior', 'monto_usd_nuevo', 'monto_bs_anterior', 'monto_bs_nuevo']);
        });
    }
};