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
        Schema::create('planmayor_costos', function (Blueprint $table) {
            $table->integer('id_costo_pm')->autoIncrement();
            $table->string('id_item')->nullable();
            $table->decimal('obra')->nullable();
            $table->decimal('reps')->nullable();
            $table->integer('id_modelo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planmayor_costos');
    }
};
