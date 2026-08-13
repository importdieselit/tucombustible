<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('abastecimientos_tanques', function (Blueprint $table) {
            $table->id();
            
            // Relaciones principales
            $table->unsignedBigInteger('id_sede');
            $table->bigInteger('id_vehiculo');
            $table->unsignedBigInteger('id_deposito');
            $table->unsignedBigInteger('id_tipo_combustible');
            $table->unsignedBigInteger('id_usuario');
            
            // Trazabilidad opcional con la precarga de origen (si aplicó)
            $table->unsignedBigInteger('id_precarga_origen')->nullable();

            $table->decimal('cantidad_litros', 11, 2);
            $table->timestamp('fecha_hora')->useCurrent();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->foreign('id_sede')->references('id')->on('sedes')->onDelete('restrict');
            $table->foreign('id_vehiculo')->references('id')->on('vehiculos')->onDelete('restrict');
            $table->foreign('id_deposito')->references('id')->on('depositos')->onDelete('restrict');
            $table->foreign('id_tipo_combustible')->references('id')->on('tipos_combustible')->onDelete('restrict');
            $table->foreign('id_usuario')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('id_precarga_origen')->references('id')->on('vehiculos_precargados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abastecimientos_tanques');
    }
};