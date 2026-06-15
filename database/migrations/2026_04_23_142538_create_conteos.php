<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConteos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conteos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // Ejemplo: AUD-202405-001
            $table->bigInteger('user_id');
            $table->text('observaciones')->nullable();
            $table->enum('estatus', ['ABIERTO', 'PROCESADO', 'ANULADO'])->default('ABIERTO');
            $table->timestamps();
        });

        Schema::create('conteo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_id')->constrained()->onDelete('cascade');
            $table->bigInteger('inventario_id');
            $table->string('ubicacion_codigo'); // El código 01-03-2-24
            $table->decimal('stock_teorico', 12, 2); // Lo que decía el sistema
            $table->decimal('stock_fisico', 12, 2);  // Lo que se contó
            $table->decimal('diferencia', 12, 2);    // Calculado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conteos');
        Schema::dropIfExists('conteo_detalles');
    }
}
