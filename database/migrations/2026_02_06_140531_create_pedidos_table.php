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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('deposito_id')->nullable();
            $table->decimal('cantidad_solicitada', 10);
            $table->decimal('cantidad_aprobada', 10)->nullable();
            $table->decimal('cantidad_recibida', 10)->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'en_proceso', 'completado', 'cancelado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->text('observaciones_admin')->nullable();
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamp('fecha_completado')->nullable();
            $table->integer('calificacion')->nullable();
            $table->text('comentario_calificacion')->nullable();
            $table->timestamps();
            $table->bigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('chofer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedidos');
    }
};
