<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrabajoExterno extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trabajos_externos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->date('fecha');
            $table->decimal('costo', 10, 2);
            $table->integer('id_usuario');
            $table->integer('id_proveedor');
            $table->integer('id_orden');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trabajos_externos');
    }
}
