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
        Schema::create('mantenimientos_programados', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('fecha')->comment('Día específico en que el mantenimiento debe realizarse.');
            $table->string('tipo', 50)->comment('Tipo de mantenimiento (ej: M1, M2, Servicio de 10k, etc.).');
            $table->text('descripcion')->nullable()->comment('Descripción breve de las tareas a realizar.');
            $table->unsignedBigInteger('orden_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->integer('km')->nullable()->comment('Kilometraje estimado al momento del mantenimiento.');
            $table->integer('estatus')->default(1)->comment('1: Programado, 2: OT Generada, 3: Cancelado.');
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
        Schema::dropIfExists('mantenimientos_programados');
    }
};
