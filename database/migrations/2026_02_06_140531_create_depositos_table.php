<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depositos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('serial', 50)->comment('Nombre del depósito (e.g., Tanque 1)');
            $table->string('ubicacion', 100)->nullable();
            $table->decimal('capacidad_litros', 11)->comment('Capacidad total en litros');
            $table->decimal('nivel_actual_litros', 11)->default(0)->comment('Nivel actual de combustible en litros');
            $table->decimal('nivel_cm', 10)->nullable();
            $table->decimal('nivel_alerta_litros', 11)->default(0)->comment('Nivel mínimo para activar la alerta de stock bajo');
            $table->timestamps();
            $table->string('producto')->nullable();
            $table->double('diametro', 8, 2)->comment('Diámetro en centímetros (cm).');
            $table->double('longitud', 8, 2)->comment('Longitud en centímetros (cm).');
            $table->double('capacidad_maxima', 8, 2)->comment('Capacidad máxima real en Litros.');
            $table->enum('forma', ['CH', 'CV', 'OH', 'OV', 'R', 'C', 'E'])->nullable()->default('CH')->comment('co Horz, CV cil. Vert, OH oval Hor,OV oval vert, Rectangular, Cuubico, esferico');
            $table->double('alto', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depositos');
    }
};
