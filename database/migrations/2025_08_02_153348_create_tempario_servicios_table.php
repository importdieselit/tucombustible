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
        Schema::create('tempario_servicios', function (Blueprint $table) {
            $table->integer('id_tempario_servicio')->autoIncrement();
            $table->integer('id_tempario_categoria');
            $table->integer('id_usuario');
            $table->string('codigo');
            $table->string('servicio');
            $table->decimal('horas');
            $table->decimal('costo');
            $table->decimal('costo_div')->nullable();
            $table->string('sumin')->nullable();
            $table->integer('id_inventario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tempario_servicios');
    }
};
