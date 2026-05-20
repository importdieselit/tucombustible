<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPesoArchivoToClienteDocumentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_documentos', function (Blueprint $table) {
            // Usamos bigInteger para soportar tamaños grandes en bytes
            $table->bigInteger('peso_archivo')->default(0)->after('ruta_archivo');
        });
    }

    public function down()
    {
        Schema::table('cliente_documentos', function (Blueprint $table) {
            $table->dropColumn('peso_archivo');
        });
    }
}
