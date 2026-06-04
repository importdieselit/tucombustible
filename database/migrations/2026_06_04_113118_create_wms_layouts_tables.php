<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. MAESTRO DE ALMACENES
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->integer('total_filas_grid')->default(10); // Alto del croquis
            $table->integer('total_columnas_grid')->default(10); // Ancho del croquis
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. BLOQUES / ESTRUCTURAS EN LA GRILLA (El Croquis)
        Schema::create('almacen_estructuras_grid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->integer('coord_x'); // Columna en la grilla
            $table->integer('coord_y'); // Fila en la grilla
            
            $table->enum('tipo_estructura', ['ESTANTE', 'GRANEL_LUBRICANTE', 'PASILLO'])->default('PASILLO');
            $table->string('codigo_bloque', 20); // Ej: "EST-01", "TANQUE-A"
            
            // Configuración si es Estante
            $table->integer('cantidad_niveles')->default(1);   // Altura (Pisos)
            $table->integer('cantidad_secciones')->default(1); // Anchura (Celdas por piso)
            $table->timestamps();

            // Evitar que dos estructuras pisen la misma coordenada en el mismo almacén
            $table->unique(['almacen_id', 'coord_x', 'coord_y'], 'uq_almacen_coordenadas');
        });

        // 3. UBICACIONES REALES (Resultado de multiplicar Niveles x Secciones)
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estructura_grid_id')->nullable()->constrained('almacen_estructuras_grid')->onDelete('cascade');
            
            $table->string('codigo_unico', 50)->unique(); // Ej: ALM1-EST01-N3-S2
            $table->integer('nivel_num')->default(1);
            $table->integer('seccion_num')->default(1);
            $table->enum('tipo_almacenamiento', ['RACK', 'PISO_GRANEL', 'PALLET'])->default('RACK');
            $table->boolean('disponible')->default(true);
            $table->timestamps();



              $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->string('codigo_ubicacion', 20)->unique(); // Ej: A-01-02-05 (Pasillo-Estante-Nivel-Posición)
            
            $table->string('pasillo', 5)->nullable();
            $table->string('estante', 5)->nullable();
            $table->string('nivel', 5)->nullable();
            $table->string('posicion', 5)->nullable();
            
            $table->enum('tipo', ['ESTANDAR', 'ZONA_GRANEL', 'PISO_PALLET', 'CUARENTENA']);
            $table->decimal('capacidad_maxima_kg', 10, 2)->nullable(); // Para tambores o racks pesados
            $table->decimal('volumen_maximo_litros', 10, 2)->nullable(); // Exclusivo para tambores de aceite
            $table->boolean('esta_bloqueada')->default(false); // Para auditorías o daños físicos
        });
    }

    public function down()
    {
        Schema::dropIfExists('ubicaciones');
        Schema::dropIfExists('almacen_estructuras_grid');
        Schema::dropIfExists('almacenes');
    }
};