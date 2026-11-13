<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIaToolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up(): void
    {
        Schema::create('ia_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre de la función');
            $table->text('description')->comment('Descripción para la IA');
            $table->boolean('is_active')->default(true);
            $table->json('required_roles')->nullable()->comment('Roles permitidos');
            $table->string('api_endpoint')->comment('Ruta a consultar');
            $table->string('http_method')->default('GET');
            $table->json('parameters_schema')->nullable()->comment('Esquema JSON de parámetros');
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
        Schema::dropIfExists('ia_tools');
    }
}
