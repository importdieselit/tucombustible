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
        Schema::create('plan_autos', function (Blueprint $table) {
            $table->integer('id_plan_auto')->autoIncrement();
            $table->integer('id_pconfig')->nullable();
            $table->integer('id_auto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_autos');
    }
};
