<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableInspeccion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('checklist_id'); // ID del blueprint usado
            $table->unsignedBigInteger('usuario_id');   // Quién realizó la inspección
            $table->enum('estatus_general', ['OK', 'ATENCION', 'ALERTA'])->default('OK');
            // Usamos 'longText' para el JSON largo, o 'json' si tu DB lo soporta bien
            $table->longText('respuesta_json'); 
            $table->timestamps();
            
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            $table->foreign('checklist_id')->references('id')->on('checklist');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspecciones');
    }
}
