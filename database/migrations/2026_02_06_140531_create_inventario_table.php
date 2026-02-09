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
        Schema::create('inventario', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('id_usuario')->nullable()->comment('Id Usuario / Empresa');
            $table->integer('prioridad')->nullable()->comment('Prioridad de producto segun su valor (1 > Normal, 2 > Alta)');
            $table->string('estatus', 11)->nullable()->comment('Estatus de Parte');
            $table->integer('id_almacen')->nullable()->comment('Almacen donde la parte esta ubicada');
            $table->string('codigo', 50)->nullable()->comment('Codigo de Parte');
            $table->string('codigo_fabricante', 50)->nullable()->comment('Codigo de Parte (Fabricante)');
            $table->string('fabricante', 100)->nullable();
            $table->string('referencia', 50)->nullable()->comment('Referencia / Ubicacion de Parte');
            $table->string('descripcion', 200)->nullable()->comment('Descripcion de la parte');
            $table->float('existencia', 10, 0)->nullable()->comment('Cantidad Existente');
            $table->decimal('costo', 10)->nullable()->comment('Costo de la Parte');
            $table->decimal('costo_div', 10)->nullable();
            $table->integer('existencia_minima')->nullable()->comment('Existencia Mínima que debe de haber en inventario');
            $table->integer('marca')->nullable()->comment('Marca de Vehiculo Relacionada');
            $table->integer('modelo')->nullable()->comment('Modelo de Marca Relacionada');
            $table->text('salida_motivo')->nullable()->comment('Motivo eliminacion de base de datos');
            $table->date('salida_fecha')->nullable()->comment('Fecha de eliminacion de base de datos');
            $table->integer('salida_id_usuario')->nullable()->comment('Usuario que elimino la parte de la base de datos');
            $table->string('fecha_in', 10)->nullable()->comment('Fecha de Ingreso a Parte o Re-Incorporación de más cantidades');
            $table->string('observacion', 200)->nullable()->comment('Observacion opcional');
            $table->text('avatar')->nullable()->comment('Avatar relacionado a la parte');
            $table->string('factura_referencia', 100)->nullable();
            $table->string('grupo', 100)->nullable();
            $table->string('codigo_interno', 100)->nullable();
            $table->boolean('clasificacion')->nullable();
            $table->boolean('incorporacion')->nullable();
            $table->integer('existencia_maxima')->default(100);
            $table->integer('condicion')->nullable();
            $table->date('fecha_cont')->nullable();
            $table->bigInteger('id_inventario_compra')->nullable()->comment('Id Orden de Compra Relacionada');
            $table->boolean('serialized')->default(false);
            $table->integer('id_compra')->nullable();
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
        Schema::dropIfExists('inventario');
    }
};
