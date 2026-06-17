<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

<<<<<<<< HEAD:database/migrations/2026_04_20_151714_add_pedido_id_to_despachos_viajes_table.php
class AddPedidoIdToDespachosViajesTable extends Migration
========
class CreateCargoTable extends Migration
>>>>>>>> main:database/migrations/2026_05_26_115838_create_cargo_table.php
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
<<<<<<<< HEAD:database/migrations/2026_04_20_151714_add_pedido_id_to_despachos_viajes_table.php
        Schema::table('despachos_viajes', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_id')->nullable()->after('cliente_id');
            $table->foreign('pedido_id')->references('id')->on('pedidos');
========
        Schema::create('cargo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
>>>>>>>> main:database/migrations/2026_05_26_115838_create_cargo_table.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
<<<<<<<< HEAD:database/migrations/2026_04_20_151714_add_pedido_id_to_despachos_viajes_table.php
        Schema::table('despachos_viajes', function (Blueprint $table) {
            //
        });
========
        Schema::dropIfExists('cargo');
>>>>>>>> main:database/migrations/2026_05_26_115838_create_cargo_table.php
    }
}
