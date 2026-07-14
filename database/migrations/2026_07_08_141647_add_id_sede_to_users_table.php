<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Añadimos la columna id_sede después del id del usuario
            $table->unsignedBigInteger('id_sede')
                  ->nullable()
                  ->after('id')
                  ->comment('Relación con la sede a la que pertenece el usuario. Null = Acceso Global / Caracas');

            // Definimos la clave foránea apuntando a tu tabla 'sedes'
            $table->foreign('id_sede')
                  ->references('id')
                  ->on('sedes')
                  ->onUpdate('no action')
                  ->onDelete('set null'); // Si se borra una sede, el usuario no se elimina, queda huérfano (null)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Es vital tumbar primero la relación foránea antes de borrar la columna
            $table->dropForeign(['id_sede']);
            $table->dropColumn('id_sede');
        });
    }
};