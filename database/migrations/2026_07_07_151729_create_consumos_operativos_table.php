<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumos_operativos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id')->comment('Vehículo interno de la flota');
            $table->unsignedBigInteger('deposito_id')->comment('Tanque específico de donde succiona');
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('cantidad_litros', 12, 2);
            
            $table->unsignedBigInteger('user_id')->comment('Operador que despacha');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos_operativos');
    }
};