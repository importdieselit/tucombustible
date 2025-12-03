<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnAtendidoPorToCaptacionClientes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('captacion_clientes', function (Blueprint $table) {
            $table->string('atendido_por')->nullable();
            $table->integer('solcitados')->default(0);
            $table->string('gestion')->default('cupo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('captacion_clientes', function (Blueprint $table) {
            $table->dropColumn('atendido_por');
            $table->dropColumn('solcitados');
            $table->dropColumn('gestion');
        });
    }
}
