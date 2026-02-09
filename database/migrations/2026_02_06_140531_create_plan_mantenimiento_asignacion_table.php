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
        Schema::create('plan_mantenimiento_asignacion', function (Blueprint $table) {
            $table->integer('id_plan_asignacion');
            $table->integer('id_usuario')->nullable();
            $table->integer('id_auto')->nullable();
            $table->integer('id_plan')->nullable();
            $table->date('fecha_in')->nullable();
            $table->date('fecha_up')->nullable();
            $table->text('observacion')->nullable();
            $table->integer('estatus')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_mantenimiento_asignacion');
    }
};
