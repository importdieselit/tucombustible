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
        Schema::create('alertas', function (Blueprint $table) {
            $table->integer('id_alerta')->primary();
            $table->integer('id_usuario')->nullable();
            $table->smallInteger('id_rel')->nullable();
            $table->integer('dias')->nullable();
            $table->date('fecha')->nullable();
            $table->text('observacion')->nullable();
            $table->boolean('estatus')->nullable();
            $table->string('accion')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alertas');
    }
};
