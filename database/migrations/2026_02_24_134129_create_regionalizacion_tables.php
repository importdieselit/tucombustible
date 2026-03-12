<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionalizacionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
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

        // Modificamos la tabla clientes para conectarla con estas nuevas tablas
        Schema::table('clientes', function (Blueprint $table) {
        // Los preparamos para IDs:
        $table->unsignedBigInteger('estado_id')->nullable()->after('email');
        $table->unsignedBigInteger('ciudad_id')->nullable()->after('estado_id');
        
        // Agregamos el campo para el "paso de registro" (1 al 11)
        $table->integer('registro_paso')->default(1)->after('status');
        $table->string('token_registro')->nullable()->after('registro_paso');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regionalizacion_tables');
    }
}
