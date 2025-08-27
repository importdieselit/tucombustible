<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->truncate();
        DB::table('users')->insert([
            [
                'id' => 1,
                'id_persona' => 1,
                'id_perfil' => 1,
                'email' => 'super@example.com',
                'name' => 'Super', // El password original era 'demo'
                'password' => Hash::make('123456789'), // El password original era 'demo'
                'status' => 1,
                'created_at' => Carbon::parse('2013-08-07'),
                'updated_at' => Carbon::now(),
                'ultimo_login' => Carbon::now(), // No se especifica, se usa la fecha actual
            ],
            [
                'id' => 2,
                'id_persona' => 2,
                'id_perfil' => 1,
                'email' => 'eliansinac@gmail.com',
                'password' => Hash::make('123456789'), // El password original era 'demo'
                'name' => 'elianSinac', // El password original era 'elianSinac'
                'status' => 1,
                'created_at' => Carbon::parse('2014-03-24'),
                'updated_at' => Carbon::now(),
                'ultimo_login' => Carbon::now(),
            ],
            [
                'id' => 3,
                'id_persona' => 3,
                'id_perfil' => 2,
                'email' => 'impormotor.cs@gmail.com',
                'name' => 'impormotor',
                'password' => Hash::make('impormotor'),
                'status' => 1,
                'created_at' => Carbon::parse('2013-12-02'),
                'updated_at' => Carbon::now(),
                'ultimo_login' => Carbon::now(),
            ]
        ]);
    }
}