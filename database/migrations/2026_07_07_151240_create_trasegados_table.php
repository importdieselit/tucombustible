<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trasegados', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_trasegado', ['interno', 'inter_sede', 'externo'])->index();
            
            // Origen
            $table->unsignedBigInteger('sede_origen_id');
            $table->unsignedBigInteger('deposito_origen_id');
            $table->enum('bolsa_origen_tipo', ['general', 'prepagado']);

            // Destino
            $table->unsignedBigInteger('sede_destino_id');
            $table->unsignedBigInteger('deposito_destino_id');
            $table->enum('bolsa_destino_tipo', ['general', 'prepagado']);

            // Si es externo (Socio/Aliado)
            $table->unsignedBigInteger('aliado_comercial_id')->nullable();

            // Producto y volumen
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('cantidad_litros', 12, 2);

            // Auditoría y estados
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['completado', 'anulado'])->default('completado');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Relaciones
            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
            $table->foreign('aliado_comercial_id')->references('id')->on('aliados_comerciales')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trasegados');
    }
};