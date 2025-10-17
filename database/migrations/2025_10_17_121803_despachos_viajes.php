<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DespachosViajes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('despachos_viajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viaje_id');
            $table->unsignedBigInteger('cliente_id')->nullable(); // Cliente registrado
            $table->string('otro_cliente')->nullable(); // Para clientes no registrados
            $table->decimal('litros', 10, 2);
            $table->timestamps();

            // Clave foránea hacia la tabla 'viajes'
            $table->foreign('viaje_id')->references('id')->on('viajes')->onDelete('cascade');

            // Clave foránea hacia la tabla 'clientes'
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('despachos_viajes');
    }
}
