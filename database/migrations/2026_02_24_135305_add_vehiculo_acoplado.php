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
            // Usamos unsignedBigInteger para que coincida con el estándar de Laravel
            $table->unsignedBigInteger('acoplado_id')->nullable()->after('id_cliente');

            // Ahora definimos la relación
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
