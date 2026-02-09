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
        Schema::create('plan_mantenimiento', function (Blueprint $table) {
            $table->integer('id_plan');
            $table->integer('id_usuario');
            $table->integer('id_marca');
            $table->text('id_modelo');
            $table->integer('tiempo');
            $table->integer('kilometraje');
            $table->string('titulo', 500);
            $table->text('descripcion');
            $table->integer('rango_min_t');
            $table->integer('rango_max_t');
            $table->integer('rango_min_k');
            $table->integer('rango_max_k');
            $table->boolean('tipo');
            $table->boolean('todas_marcasmodelos');
            $table->string('short', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_mantenimiento');
    }
};
