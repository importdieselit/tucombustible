<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_facturas_compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('compra_id');
            $table->unsignedBigInteger('usuario_id');
            $table->string('numero_factura_anterior')->nullable();
            $table->string('numero_factura_nuevo');
            $table->string('factura_path_anterior')->nullable();
            $table->string('factura_path_nuevo');
            $table->timestamps();

            $table->foreign('compra_id')->references('id')->on('compras_combustible')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_facturas_compras');
    }
};
