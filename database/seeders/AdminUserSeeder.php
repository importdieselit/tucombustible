<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@empresa.com',
            'password' => Hash::make('admin123'),
            'id_perfil' => 1,
            'id_persona' => 0,
            'email_verified_at' => now(),
        ]);
    }
}
