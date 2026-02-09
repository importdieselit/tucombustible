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
        Schema::create('inventario_old', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->integer('prioridad')->nullable()->comment('1 > Normal, 2 > Alta');
            $table->integer('estatus')->nullable();
            $table->unsignedBigInteger('id_inventario_almacen')->nullable();
            $table->string('codigo', 50)->nullable()->comment('Codigo de Parte');
            $table->string('codigo_fabricante', 50)->nullable()->comment('Codigo de Parte (Fabricante)');
            $table->string('fabricante', 100)->nullable();
            $table->string('referencia', 50)->nullable()->comment('Referencia / Ubicacion de Parte');
            $table->string('descripcion', 200)->nullable()->comment('Descripcion de la parte');
            $table->double('existencia')->nullable();
            $table->decimal('costo', 10)->nullable();
            $table->decimal('costo_div', 10)->nullable();
            $table->integer('existencia_minima')->nullable();
            $table->integer('marca')->nullable();
            $table->integer('modelo')->nullable();
            $table->text('salida_motivo')->nullable();
            $table->date('salida_fecha')->nullable();
            $table->unsignedBigInteger('salida_id_usuario')->nullable();
            $table->string('fecha_in', 10)->nullable();
            $table->string('observacion', 200)->nullable();
            $table->text('avatar')->nullable();
            $table->string('factura_referencia', 100)->nullable();
            $table->string('grupo', 100)->nullable();
            $table->string('codigo_interno', 100)->nullable();
            $table->tinyInteger('clasificacion')->nullable();
            $table->tinyInteger('incorporacion')->nullable();
            $table->integer('existencia_maxima')->default(100);
            $table->integer('condicion')->nullable();
            $table->date('fecha_cont')->nullable();
            $table->tinyInteger('serialized')->default(0);
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
        Schema::dropIfExists('inventario_old');
    }
};
