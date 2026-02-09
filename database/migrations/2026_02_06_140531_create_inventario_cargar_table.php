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
        Schema::create('inventario_cargar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carga');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_inventario')->nullable();
            $table->date('fecha_in');
            $table->decimal('costo', 10);
            $table->double('cantidad')->default(0);
            $table->text('observacion');
            $table->string('referencia', 200);
            $table->unsignedBigInteger('proveedor')->nullable();
            $table->string('otro_proveedor', 200);
            $table->unsignedBigInteger('emisor');
            $table->double('existencia')->nullable();
            $table->string('tipo', 1)->default('C');
            $table->time('hora')->nullable();
            $table->string('status', 15)->default('RECIBIDO');
            $table->decimal('costo_div', 10, 0);
            $table->string('id_cotizacion', 50)->nullable();
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
        Schema::dropIfExists('inventario_cargar');
    }
};
