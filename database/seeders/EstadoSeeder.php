<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Estado::insert([
            [ "nombre" => "Anzoátegui", "created_at" => now()],
            [ "nombre" => "Sucre","created_at" => now()],
            [ "nombre" => "Amazonas","created_at" => now()],
            [ "nombre" => "Apure","created_at" => now()],
            [ "nombre" => "Aragua","created_at" => now()],
            [ "nombre" => "Barinas","created_at" => now()],
            [ "nombre" => "Bolívar", "created_at" => now()],
            [ "nombre" => "Carabobo","created_at" => now()],
            [ "nombre" => "Cojedes", "created_at" => now()],
            [ "nombre" => "Delta Amacuro", "created_at" => now()],
            [ "nombre" => "Distrito Capital", "created_at" => now()],
            [ "nombre" => "Falcón","created_at" => now()],
            [ "nombre" => "Guarico", "created_at" => now()],
            [ "nombre" => "La Guaira","created_at" => now()],
            [ "nombre" => "Lara", "created_at" => now()],
            [ "nombre" => "Merida", "created_at" => now()],
            [ "nombre" => "Miranda", "created_at" => now()],
            [ "nombre" => "Monagas", "created_at" => now()],
            [ "nombre" => "Nueva Esparta", "created_at" => now()],
            [ "nombre" => "Portuguesa", "created_at" => now()],
            [ "nombre" => "Tachira", "created_at" => now()],
            [ "nombre" => "Trujillo", "created_at" => now()],
            [ "nombre" => "Yaracuy", "created_at" => now()],
            [ "nombre" => "Zulia", "created_at" => now()],

        ]);

    }
}
