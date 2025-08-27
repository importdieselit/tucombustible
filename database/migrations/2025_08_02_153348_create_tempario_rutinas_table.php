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
        Schema::create('tempario_rutinas', function (Blueprint $table) {
            $table->integer('id_rutina')->autoIncrement();
            $table->integer('id_tempario');
            $table->integer('id_plan')->nullable();
            $table->integer('ord')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tempario_rutinas');
    }
};
