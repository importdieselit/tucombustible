<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIaSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('ia_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique()->comment('Identificador de la configuración (ej. system_prompt)');
                $table->text('value')->nullable()->comment('Valor de la configuración');
                $table->string('description')->nullable()->comment('Descripción para el panel de administración');
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
        Schema::dropIfExists('ia_settings');
    }
}
