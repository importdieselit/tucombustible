<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardiasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('guardias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->foreign('personal_id')->references('id_personal')->on('personal')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('rol_guardia', ['Chofer', 'Ayudante de Chofer', 'Mecanico']); // El rol en el que ejerce la guardia
            $table->timestamps();
            
            // Evitar que una persona tenga doble guardia asignada el mismo día
            $table->unique(['personal_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardias');
    }
}
