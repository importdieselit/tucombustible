<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanMayorControlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_mayor_controles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id'); 
            $table->unsignedBigInteger('mantenimiento_item_id');
            $table->timestamps();

            $table->foreign('mantenimiento_item_id')->references('id')->on('mantenimiento_items')->onDelete('cascade');
            // Aquí puedes agregar el foreign key de tu tabla unidades si aplica:
            // $table->foreign('vehiculo_id')->references('id')->on('vehiculos');
            
            // Índice único para evitar duplicados en la matriz
            $table->unique(['vehiculo_id', 'mantenimiento_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_mayor_controles');
    }
}
