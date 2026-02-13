<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importante añadir esto

class FixUsersIdIncrement extends Migration
{
    public function up()
    {
        // Usamos una sentencia SQL cruda porque es más efectiva para arreglar la estructura de la PK
        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
    }

    public function down()
    {
        // Para revertir, quitamos el auto_increment (pero dejarlo como PK es lo normal)
        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL');
    }
}