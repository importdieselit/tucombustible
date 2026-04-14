<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTablaDestinoAndCampoDestinoToTipoDocumento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_documento', function (Blueprint $table) {
            $table->string('tabla_destino')->nullable()->after('tipo');
            $table->string('campo_destino')->nullable()->after('tabla_destino');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipo_documento', function (Blueprint $table) {
            $table->dropColumn('tabla_destino');
            $table->dropColumn('campo_destino');
        });
    }
}
