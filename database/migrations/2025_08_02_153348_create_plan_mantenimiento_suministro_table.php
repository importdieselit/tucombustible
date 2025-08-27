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
        Schema::create('plan_mantenimiento_suministro', function (Blueprint $table) {
            $table->integer('id_plan_suministro')->autoIncrement();
            $table->integer('id_usuario');
            $table->integer('id_plan');
            $table->integer('id_inventario');
            $table->integer('cantidad');
            $table->integer('id_modelo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mantenimiento_suministro');
    }
};
