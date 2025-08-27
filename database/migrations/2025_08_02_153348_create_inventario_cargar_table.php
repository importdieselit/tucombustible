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
        Schema::create('inventario_cargar', function (Blueprint $table) {
            $table->bigInteger('id_carga')->autoIncrement();
            $table->bigInteger('id_usuario')->nullable();
            $table->bigInteger('id_inventario')->nullable();
            $table->date('fecha_in');
            $table->decimal('costo');
            $table->double('cantidad')->default('0');
            $table->text('observacion');
            $table->string('referencia');
            $table->bigInteger('proveedor')->nullable();
            $table->string('otro_proveedor');
            $table->bigInteger('emisor');
            $table->double('existencia')->nullable();
            $table->string('tipo')->default('C');
            $table->string('hora')->nullable();
            $table->string('status')->default('RECIBIDO');
            $table->decimal('costo_div');
            $table->string('id_cotizacion')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_cargar');
    }
};
