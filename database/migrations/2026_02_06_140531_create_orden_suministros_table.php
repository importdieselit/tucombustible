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
        Schema::create('orden_suministros', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('orden_id');
            $table->unsignedInteger('inventario_id')->nullable()->comment('ID del ítem de inventario (si aplica)');
            $table->unsignedInteger('cantidad_solicitada')->comment('Cantidad del suministro solicitada');
            $table->boolean('es_manual')->default(false)->comment('Indica si el suministro fue agregado manualmente');
            $table->string('descripcion')->nullable()->comment('Descripción del suministro (si es manual)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orden_suministros');
    }
};
