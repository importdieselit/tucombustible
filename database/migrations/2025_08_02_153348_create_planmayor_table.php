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
        Schema::create('planmayor', function (Blueprint $table) {
            $table->integer('id_plan_mayor')->autoIncrement();
            $table->integer('id_auto')->nullable();
            $table->integer('item')->nullable();
            $table->integer('val')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planmayor');
    }
};
