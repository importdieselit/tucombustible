<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermisoPerfil extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permiso_perfil', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_perfil')->nullable();
            $table->unsignedBigInteger('id_modulo')->nullable();
            $table->foreign('id_modulo')->references('id')->on('modulos')->onDelete('cascade');
            $table->foreign('id_perfil')->references('id')->on('perfiles')->onDelete('cascade');
            $table->boolean('create')->default(false);
            $table->boolean('read')->default(false);
            $table->boolean('update')->default(false);
            $table->boolean('delete')->default(false);
                
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
        Schema::dropIfExists('permiso_perfil');
    }
}
