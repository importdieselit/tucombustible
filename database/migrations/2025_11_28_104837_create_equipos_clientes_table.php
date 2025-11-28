<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiposClientesTable extends Migration
{
    public function up()
    {
        Schema::create('equipos_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('captacion_id');
            $table->string('tipo_equipo')->nullable();
            $table->string('identificador')->nullable();
            $table->json('caracteristicas')->nullable();
            $table->timestamps();

            $table->foreign('captacion_id')->references('id')->on('captacion_clientes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipos_clientes');
    }
}
