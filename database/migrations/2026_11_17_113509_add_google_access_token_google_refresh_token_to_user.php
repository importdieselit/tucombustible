<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleAccessTokenGoogleRefreshTokenToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()    
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_access_token')->nullable();
            $table->text('google_refesh_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_access_token');
            $table->dropColumn('google_refesh_token');

        });
    }
}
