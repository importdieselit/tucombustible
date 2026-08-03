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
            
            // Origen (opcional para trasegados externos de Entrada / Préstamo recibido)
            $table->unsignedBigInteger('sede_origen_id')->nullable();
            $table->unsignedBigInteger('deposito_origen_id')->nullable();
            $table->enum('bolsa_origen_tipo', ['general', 'prepagado'])->nullable();

            // Destino (opcional para trasegados externos de Salida / Préstamo otorgado)
            $table->unsignedBigInteger('sede_destino_id')->nullable();
            $table->unsignedBigInteger('deposito_destino_id')->nullable();
            $table->enum('bolsa_destino_tipo', ['general', 'prepagado'])->nullable();

            // Cliente / Aliado Comercial (aplica para tipo_trasegado = 'externo')
            $table->unsignedBigInteger('cliente_id')->nullable();

            // Producto y volumen
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->decimal('cantidad_litros', 12, 2);

            // Auditoría y estados
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['completado', 'anulado'])->default('completado');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Relaciones / Llaves Foráneas
            $table->foreign('sede_origen_id')->references('id')->on('sedes')->onDelete('restrict');
            $table->foreign('deposito_origen_id')->references('id')->on('depositos')->onDelete('restrict');
            $table->foreign('sede_destino_id')->references('id')->on('sedes')->onDelete('restrict');
            $table->foreign('deposito_destino_id')->references('id')->on('depositos')->onDelete('restrict');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('restrict');
            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trasegados');
    }
};