<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUbicacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('ubicaciones', function (Blueprint $table) {
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
            
            $table->timestamps();
            
            $table->index(['almacen_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ubicaciones');
    }
}
