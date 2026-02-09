<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('viaticos_viaje', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('viaje_id');
            $table->string('concepto');
            $table->decimal('monto_base');
            $table->integer('cantidad');
            $table->decimal('monto_ajustado')->nullable();
            $table->unsignedBigInteger('ajustado_por')->nullable();
            $table->boolean('es_editable')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('viaticos_viaje');
    }
};
