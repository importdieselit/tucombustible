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
        Schema::create('tempario_categorias', function (Blueprint $table) {
            $table->integer('id_tempario_categoria')->comment('Id');
            $table->integer('id_usuario')->comment('Id Usuario / Empresa');
            $table->string('codigo', 60)->comment('Codigo Opcional Asignado');
            $table->string('categoria', 100)->comment('Nombre de Categoria');
            $table->float('costo_mo', 10)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tempario_categorias');
    }
};
