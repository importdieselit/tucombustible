<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('id_usuario')->nullable();
            $table->integer('nro_orden')->nullable();
            $table->string('tipo')->nullable();
            $table->string('estatus')->nullable();
            $table->bigInteger('id_vehiculo')->nullable();
            $table->integer('kilometraje')->nullable();
            $table->text('descripcion_1')->nullable();
            $table->text('descripcion_2')->nullable();
            $table->text('descripcion_3')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('id_us_in')->nullable();
            $table->date('fecha_in')->nullable();
            $table->string('hora_in')->nullable();
            $table->date('fecha_out')->nullable();
            $table->string('hora_out')->nullable();
            $table->integer('id_us_out')->nullable();
            $table->integer('id_plan')->default('0');
            $table->text('observacion')->nullable();
            $table->integer('origen')->nullable();
            $table->text('anulacion')->nullable();
            $table->string('chfr')->nullable();
            $table->string('responsable')->nullable()->default('Sin Indicar');
            $table->integer('parent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
