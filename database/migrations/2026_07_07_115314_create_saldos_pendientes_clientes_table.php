<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldos_pendientes_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->index();
            $table->unsignedBigInteger('tipo_combustible_id');
            
            // Transacción del pendiente
            $table->enum('tipo_accion', ['acumulado', 'consumido'])->comment('acumulado por reverso, consumido por despacho');
            $table->decimal('cantidad_litros', 12, 2);
            
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario que procesó el movimiento');
            $table->text('observaciones')->nullable();
            
            $table->timestamps();

            $table->foreign('tipo_combustible_id')->references('id')->on('tipos_combustible')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos_pendientes_clientes');
    }
};
