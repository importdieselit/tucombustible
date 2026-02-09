<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checklist', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('titulo')->nullable()->comment('Descripción del checklist');
            $table->integer('tipo')->nullable();
            $table->boolean('activo')->default(true);
            $table->longText('checklist')->comment('Estructura completa del checklist en formato JSON.');
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
        Schema::dropIfExists('checklist');
    }
};
