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
            // Limpieza preventiva si la tabla quedó creada tras un intento fallido
            Schema::dropIfExists('guardias');

            Schema::create('guardias', function (Blueprint $table) {
            Schema::dropIfExists('guardias');

            $table->id();
            $table->integer('personal_id'); // INT con signo para emparejar con personal.id_personal
            $table->date('fecha');
            $table->enum('rol_guardia', ['Chofer', 'Ayudante de Chofer', 'Mecanico']);
            $table->timestamps();

            $table->foreign('personal_id')->references('id_personal')->on('personal')->onDelete('cascade');
            $table->unique(['personal_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardias');
    }
}