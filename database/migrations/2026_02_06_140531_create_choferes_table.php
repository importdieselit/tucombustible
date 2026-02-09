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
        Schema::create('choferes', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('licencia_numero')->nullable();
            $table->date('licencia_vencimiento')->nullable();
            $table->string('documento_vialidad_numero')->nullable();
            $table->date('documento_vialidad_vencimiento')->nullable();
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('persona_id');
            $table->timestamps();
            $table->string('certificado_medico')->nullable();
            $table->date('certificado_medico_vencimiento')->nullable();
            $table->string('soporte_licencia')->nullable();
            $table->string('soporte_certificado')->nullable();
            $table->string('soporte_documento')->nullable();
            $table->string('tipo_licencia')->nullable();
            $table->string('cargo')->nullable();
            $table->text('foto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('choferes');
    }
};
