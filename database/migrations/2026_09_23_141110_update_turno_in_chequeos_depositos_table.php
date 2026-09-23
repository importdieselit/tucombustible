<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Permitir temporalmente 'Vespertino' junto a los valores anteriores
        DB::statement("ALTER TABLE `chequeos_depositos` MODIFY COLUMN `turno` ENUM('Matutino', 'Nocturno', 'Vespertino') NOT NULL COLLATE 'utf8mb4_unicode_ci'");

        // 2. Actualizar todos los registros existentes
        DB::table('chequeos_depositos')
            ->where('turno', 'Nocturno')
            ->update(['turno' => 'Vespertino']);

        // 3. Definir el ENUM final retirando 'Nocturno'
        DB::statement("ALTER TABLE `chequeos_depositos` MODIFY COLUMN `turno` ENUM('Matutino', 'Vespertino') NOT NULL COLLATE 'utf8mb4_unicode_ci'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `chequeos_depositos` MODIFY COLUMN `turno` ENUM('Matutino', 'Nocturno', 'Vespertino') NOT NULL COLLATE 'utf8mb4_unicode_ci'");

        DB::table('chequeos_depositos')
            ->where('turno', 'Vespertino')
            ->update(['turno' => 'Nocturno']);

        DB::statement("ALTER TABLE `chequeos_depositos` MODIFY COLUMN `turno` ENUM('Matutino', 'Nocturno') NOT NULL COLLATE 'utf8mb4_unicode_ci'");
    }
};