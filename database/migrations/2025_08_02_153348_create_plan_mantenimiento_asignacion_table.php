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
        Schema::create('plan_mantenimiento_asignacion', function (Blueprint $table) {
            $table->integer('id_plan_asignacion')->autoIncrement();
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
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mantenimiento_asignacion');
    }
};
