<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePagos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_pedido')->nullable();
            $table->decimal('litros', 8, 2);
            $table->string('referencia', 255);
            $table->dateTime('fecha_pago');
            $table->dateTime('fecha_solicitud');
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('id_pedido')->references('id')->on('pedidos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['id_usuario']);
            $table->dropForeign(['id_cliente']);
            $table->dropForeign(['id_pedido']);
        });
        Schema::dropIfExists('pagos');
        Schema::enableForeignKeyConstraints();
    }   
}
