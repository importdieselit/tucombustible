<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Añadir dimensiones del plano a tu tabla de almacenes ya existente
        Schema::table('almacenes', function (Blueprint $table) {
            $table->integer('total_filas_grid')->default(10)->after('nombre');
            $table->integer('total_columnas_grid')->default(10)->after('total_filas_grid');
        });

        // 2. Crear la tabla del Croquis Virtual (Estructura de la Grilla)
        Schema::create('almacen_estructuras_grid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->integer('coord_x'); // Columna en la grilla
            $table->integer('coord_y'); // Fila en la grilla
            $table->enum('tipo_estructura', ['ESTANTE', 'GRANEL_LUBRICANTE', 'PASILLO', 'PISO_PALLET'])->default('PASILLO');
            $table->string('codigo_bloque', 20); // Ej: "EST-01"
            $table->integer('cantidad_niveles')->default(1);
            $table->integer('cantidad_secciones')->default(1);
            $table->timestamps();

            $table->unique(['almacen_id', 'coord_x', 'coord_y'], 'uq_almacen_coordenadas');
        });

        // 3. Alterar tu tabla 'ubicaciones' actual para enlazarla al croquis de forma segura (nullable)
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->foreignId('estructura_grid_id')
                  ->nullable()
                  ->after('almacen_id')
                  ->constrained('almacen_estructuras_grid')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropForeign(['estructura_grid_id']);
            $table->dropColumn('estructura_grid_id');
        });

        Schema::dropIfExists('almacen_estructuras_grid');

        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropColumn(['total_filas_grid', 'total_columnas_grid']);
        });
    }
};