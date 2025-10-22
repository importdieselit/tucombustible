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
        Schema::create('inventario', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('id_usuario')->nullable();
            $table->integer('prioridad')->nullable();
            $table->integer('estatus')->nullable();
            $table->bigInteger('id_almacen')->nullable();
            $table->string('codigo')->nullable();
            $table->string('codigo_fabricante')->nullable();
            $table->string('fabricante')->nullable();
            $table->string('referencia')->nullable();
            $table->string('descripcion')->nullable();
            $table->double('existencia')->nullable();
            $table->decimal('costo')->nullable();
            $table->decimal('costo_div')->nullable();
            $table->integer('existencia_minima')->nullable();
            $table->integer('marca')->nullable();
            $table->integer('modelo')->nullable();
            $table->text('salida_motivo')->nullable();
            $table->date('salida_fecha')->nullable();
            $table->bigInteger('salida_id_usuario')->nullable();
            $table->string('fecha_in')->nullable();
            $table->string('observacion')->nullable();
            $table->text('avatar')->nullable();
            $table->string('factura_referencia')->nullable();
            $table->string('grupo')->nullable();
            $table->string('codigo_interno')->nullable();
            $table->integer('clasificacion')->nullable();
            $table->integer('incorporacion')->nullable();
            $table->integer('existencia_maxima')->default('100');
            $table->integer('condicion')->nullable();
            $table->date('fecha_cont')->nullable();
            $table->integer('serialized')->default('0');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
