<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChoferesClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('choferes_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id'); // A qué sede pertenece
            $table->string('nombre_completo');
            $table->integer('cedula')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Relación con tu tabla de clientes
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('choferes_clientes');
    }
}
