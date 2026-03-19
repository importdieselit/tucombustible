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
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_perfil')->nullable();
            $table->unsignedBigInteger('id_persona');
            $table->integer('cliente_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('id_master')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->integer('status')->default(1);
            $table->time('ultimo_login')->nullable();
            $table->text('fcm_token')->nullable();
            $table->string('telegram_id', 100)->nullable();
            $table->string('telegram_username', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
