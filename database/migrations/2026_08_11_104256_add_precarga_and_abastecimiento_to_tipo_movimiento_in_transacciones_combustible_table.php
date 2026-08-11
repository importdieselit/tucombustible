<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
   
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `transacciones_combustible` 
            MODIFY COLUMN `tipo_movimiento` ENUM(
                'compra',
                'despacho',
                'trasegado_interno',
                'trasegado_inter-sede',
                'trasegado_externo',
                'reverso',
                'consumo_operativo',
                'ajuste_positivo',
                'ajuste_negativo',
                'despacho_prepagado',
                'compromiso_despacho',
                'precarga',
                'abastecimiento'
            ) NOT NULL COLLATE 'utf8mb4_unicode_ci'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `transacciones_combustible` 
            MODIFY COLUMN `tipo_movimiento` ENUM(
                'compra',
                'despacho',
                'trasegado_interno',
                'trasegado_inter-sede',
                'trasegado_externo',
                'reverso',
                'consumo_operativo',
                'ajuste_positivo',
                'ajuste_negativo',
                'despacho_prepagado',
                'compromiso_despacho'
            ) NOT NULL COLLATE 'utf8mb4_unicode_ci'
        ");
    }
};