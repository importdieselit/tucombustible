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
        Schema::create('inspecciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('checklist_id');
            $table->unsignedBigInteger('usuario_id');
            $table->enum('estatus_general', ['OK', 'WARNING', 'ALERT'])->default('OK');
            $table->longText('respuesta_json');
            $table->longText('respuesta_in')->nullable();
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
        Schema::dropIfExists('inspecciones');
    }
};
