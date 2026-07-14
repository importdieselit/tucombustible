<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversos_combustible', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viaje_id')->comment('Viaje fallido en el módulo Logística');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('cantidad_litros', 12, 2);
            
            $table->string('motivo_reverso')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversos_combustible');
    }
};