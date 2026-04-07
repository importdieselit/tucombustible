<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMgoFieldsToPedidosTable extends Migration
{
    public function up()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('muelle_atraque')->nullable()->after('estado');
            $table->string('buque_nombre')->nullable()->after('muelle_atraque');
            $table->string('imo_bandera')->nullable()->after('buque_nombre');
        });
    }

    public function down()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['muelle_atraque', 'buque_nombre', 'imo_bandera']);
        });
    }
}
