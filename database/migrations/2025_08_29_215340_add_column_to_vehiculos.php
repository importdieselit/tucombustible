<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToVehiculos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('rotc')->nullable(); // Ejemplo de agregar una nueva columna
            $table->string('rotc_venc')->nullable(); 
            $table->string('rcv')->nullable(); 
            $table->string('racda')->nullable(); 
            $table->string('semcamer')->nullable(); 
            $table->string('homologacion_intt')->nullable();
            $table->string('permiso_intt')->nullable();
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
            $table->dropColumn(['rotc', 'rotc_venc', 'rcv', 'racda', 'semcamer', 'homologacion_intt', 'permiso_intt']);
        });
    }
}
