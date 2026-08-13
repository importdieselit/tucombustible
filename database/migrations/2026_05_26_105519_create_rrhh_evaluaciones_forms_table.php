<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRrhhEvaluacionesFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('rrhh_evaluaciones_forms', function (Blueprint $table) {

            $table->id();

            $table->string('nombre');

            $table->unsignedBigInteger('cargo_id');

            $table->text('google_form_url');

            $table->boolean('activo')->default(true);

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
        Schema::dropIfExists('rrhh_evaluaciones_forms');
    }
}
