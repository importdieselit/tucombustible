<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Validación, creación y relación de 'id_sede'
        if (!Schema::hasColumn('depositos', 'id_sede')) {
            Schema::table('depositos', function (Blueprint $table) {
                $table->unsignedBigInteger('id_sede')->nullable()->after('id');
                $table->foreign('id_sede')->references('id')->on('sedes')->nullOnDelete();
            });
        }

        // 2. Validación, creación y relación de 'tipo_combustible_id'
        if (!Schema::hasColumn('depositos', 'tipo_combustible_id')) {
            Schema::table('depositos', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_combustible_id')->nullable()->after('producto');
                $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->nullOnDelete();
            });
        }

        // 3. Validación y creación del campo geométrico 'ancho'
        if (!Schema::hasColumn('depositos', 'ancho')) {
            Schema::table('depositos', function (Blueprint $table) {
                $table->double('ancho', 8, 2)->nullable()->after('longitud')
                      ->comment('Ancho en centímetros (cm) para formas rectangulares.');
            });
        }
    }

    public function down(): void
    {
        // Para mantener la consistencia, el método down también debe ser a prueba de errores
        Schema::table('depositos', function (Blueprint $table) {
            
            if (Schema::hasColumn('depositos', 'id_sede')) {
                // Es buena práctica eliminar primero la foránea pasando un arreglo con el nombre de la columna
                $table->dropForeign(['id_sede']);
                $table->dropColumn('id_sede');
            }

            if (Schema::hasColumn('depositos', 'tipo_combustible_id')) {
                $table->dropForeign(['tipo_combustible_id']);
                $table->dropColumn('tipo_combustible_id');
            }

            if (Schema::hasColumn('depositos', 'ancho')) {
                $table->dropColumn('ancho');
            }
        });
    }
};
