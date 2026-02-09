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
        Schema::create('planmayor_costos', function (Blueprint $table) {
            $table->integer('id_costo_pm');
            $table->string('id_item')->nullable();
            $table->decimal('obra', 10)->nullable();
            $table->decimal('reps', 10)->nullable();
            $table->integer('id_modelo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('planmayor_costos');
    }
};
