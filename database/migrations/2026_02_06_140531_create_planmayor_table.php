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
        Schema::create('planmayor', function (Blueprint $table) {
            $table->integer('id_plan_mayor');
            $table->integer('id_auto')->nullable();
            $table->integer('item')->nullable();
            $table->integer('val')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('planmayor');
    }
};
