<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCuposRegionalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cupos_regionales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ciudad_id');
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('total_asignado', 10, 2);
            $table->timestamps();

            $table->foreign('ciudad_id')->references('id')->on('ciudades');
            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cupos_regionales');
    }
}
