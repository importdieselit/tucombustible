<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIdCargoToPersonal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal', function (Blueprint $table) {
                $table->unsignedBigInteger('cargo_id')->nullable()->after('id_personal');
                $table->foreign('cargo_id')->references('id')->on('cargo')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->dropForeign(['cargo_id']);
            $table->dropColumn('cargo_id'); 
        });
    }
}
