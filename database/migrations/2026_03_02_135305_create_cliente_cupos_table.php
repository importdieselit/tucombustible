<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClienteCuposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cliente_cupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id'); 
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('litros_solicitados', 8, 2)->default(0);
            $table->decimal('litros_aprobados', 8, 2)->default(0);
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
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
        Schema::dropIfExists('cliente_cupos');
    }
}
