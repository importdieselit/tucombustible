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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('deposito_id');
            $table->decimal('cantidad_solicitada', 10, 2); // Cantidad en litros
            $table->decimal('cantidad_aprobada', 10, 2)->nullable(); // Cantidad aprobada por admin
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'en_proceso', 'completado', 'cancelado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->text('observaciones_admin')->nullable();
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamp('fecha_completado')->nullable();
            $table->integer('calificacion')->nullable(); // 1-5 estrellas
            $table->text('comentario_calificacion')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('deposito_id')->references('id')->on('depositos')->onDelete('cascade');

            // Índices
            $table->index(['cliente_id', 'estado']);
            $table->index(['deposito_id']);
            $table->index('fecha_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
