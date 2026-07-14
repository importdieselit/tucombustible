<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones_combustible', function (Blueprint $table) {
            $table->id();
            
            // Dimensiones obligatorias de segmentación
            $table->unsignedBigInteger('sede_id')->index(); 
            $table->unsignedBigInteger('tipo_combustible_id');
            $table->enum('bolsa_tipo', ['general', 'prepagado'])->index();
            
            // Tipo de movimiento operativo del nuevo flujo
            $table->enum('tipo_movimiento', [
                'compra', 
                'despacho', 
                'trasegado', 
                'reverso', 
                'consumo_operativo', 
                'ajuste_merma',
                'despacho_prepagado',
                'ingreso_reverso'
            ])->index();

            // Cantidad con signo y decimales (Positivo = Entra, Negativo = Sale)
            $table->decimal('cantidad_litros', 12, 2);

            // Relaciones opcionales para la trazabilidad/auditoría (Polimórfica o FKs explícitas)
            $table->unsignedBigInteger('deposito_id')->nullable()->comment('Tanque físico de origen/destino');
            $table->unsignedBigInteger('viaje_id')->nullable()->comment('Vínculo con el módulo de Logística');
            $table->unsignedBigInteger('cliente_id')->nullable()->comment('Para reversos o consumos asociados');
            
            // Auditoría básica
            $table->unsignedBigInteger('user_id')->nullable()->comment('Operador que ejecutó la acción manual');
            $table->text('observaciones')->nullable();
            
            $table->timestamps();

            // Llaves foráneas de seguridad
            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
            // $table->foreign('deposito_id')->references('id')->on('depositos')->onDelete('restrict'); // Descomenta si tu tabla se llama depositos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_combustible');
    }
};
