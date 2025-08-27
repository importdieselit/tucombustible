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
        Schema::create('plan_mantenimiento_aplicado', function (Blueprint $table) {
            $table->integer('id_aplicado')->autoIncrement();
            $table->integer('id_auto');
            $table->integer('id_orden');
            $table->integer('semana');
            $table->date('fecha');
            $table->integer('id_plan');
            $table->integer('nro_orden');
            $table->string('tipo');
            $table->integer('km')->default('0');
            $table->integer('ord')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mantenimiento_aplicado');
    }
};
