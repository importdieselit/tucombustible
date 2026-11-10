<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrecioBunkerHistoricosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('precios_bunker_historico', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('combustible', 50); // Aquí irá 'VLSFO', 'MGO', 'BIO', etc.
            $table->decimal('precio', 8, 2);
            $table->timestamps();

            // MAGIA ESCALABLE: Aseguramos que un mismo combustible no se duplique en el mismo día.
            $table->unique(['fecha', 'combustible']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('precio_bunker_historicos');
    }
}
