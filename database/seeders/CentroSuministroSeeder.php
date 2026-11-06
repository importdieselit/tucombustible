<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CentroSuministroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $centros = [
            ['nombre' => 'Bolipuertos Maracaibo', 'direccion' => 'Dirección A', 'procedimiento' => '["TTS","STS","ALONGSITE"]'],
            ['nombre' => 'Bolipuertos La Guaira', 'direccion' => 'Dirección B', 'procedimiento' => '["TTS","STS","ALONGSITE"]'],
            ['nombre' => 'Puerto Cabello', 'direccion' => 'Dirección D', 'procedimiento' => '["TTS","STS","ALONGSITE"]'],
            ['nombre' => 'Puerto La Cruz', 'direccion' => 'Dirección E', 'procedimiento' => '["TTS","STS"]'],
            ['nombre' => 'Guanta', 'direccion' => 'Dirección F', 'procedimiento' => '["TTS","ALONGSITE"]'],
            ['nombre' => 'Punta de Cardon', 'direccion' => 'Dirección G', 'procedimiento' => '["TTS","STS"]'],
            ['nombre' => 'Margarita', 'direccion' => 'Dirección H', 'procedimiento' => '["TTS","STS"]'],
            ['nombre' => 'Boca de Serpiente', 'direccion' => 'Dirección J', 'procedimiento' => '["TTS","STS"]'],
            ['nombre' => 'Guamache', 'direccion' => 'Dirección K', 'procedimiento' => '["TTS","ALONGSITE"]'],
        ];

        foreach ($centros as $centro) {
            \App\Models\CentroSuministro::create($centro);
        }
    }
}
