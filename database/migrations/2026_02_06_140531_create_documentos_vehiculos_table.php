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
        Schema::create('documentos_vehiculos', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->integer('vehiculo_id');
            $table->string('tipo', 200);
            $table->longText('doc');
            $table->date('fecha_in');
            $table->date('fecha_venc');
            $table->string('nro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentos_vehiculos');
    }
};
