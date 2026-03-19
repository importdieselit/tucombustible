<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
class CreateRegionalizacionTables extends Migration
{
    public function up()
    {
        // Tabla de Estados
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });
 
        // Tabla de Ciudades relacionada con Estados
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->timestamps();
        });
 
        // NOTA: Los campos estado_id, ciudad_id, registro_paso y token_registro
        // ya están definidos directamente en la migración original de clientes.
        // El ALTER TABLE fue eliminado para evitar duplicación.
    }
 
    public function down()
    {
        Schema::dropIfExists('ciudades');
        Schema::dropIfExists('estados');
    }
}