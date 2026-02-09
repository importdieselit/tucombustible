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
        Schema::create('tempario_servicios', function (Blueprint $table) {
            $table->integer('id_tempario_servicio')->comment('Id');
            $table->integer('id_tempario_categoria')->comment('Id Tempario Categoria');
            $table->integer('id_usuario')->comment('Id Usuario / Empresa');
            $table->string('codigo', 30)->comment('Codigo Opcional de Item');
            $table->string('servicio', 200)->comment('Servicio');
            $table->decimal('horas', 10)->comment('Horas de Trabajo para el Servicio');
            $table->decimal('costo', 10)->comment('Costo por Hora para el Servicio');
            $table->decimal('costo_div', 10)->nullable();
            $table->string('sumin')->nullable();
            $table->integer('id_inventario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tempario_servicios');
    }
};
