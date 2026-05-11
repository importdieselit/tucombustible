<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehiculoAcoplado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            // Quitamos el 'unsigned' para que sea BIGINT igual que el ID
            $table->bigInteger('acoplado_id')->nullable()->after('id_cliente');

            $table->foreign('acoplado_id')
                ->references('id')
                ->on('vehiculos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['acoplado_id']);
            $table->dropColumn('acoplado_id');
        });
    }
}
