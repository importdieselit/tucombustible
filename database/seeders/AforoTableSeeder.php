<?php

namespace   Database\Seeders;
// database/seeders/AforoTableSeeder.php

use App\Models\Deposito;
use App\Models\Aforo;
use App\Services\AforoCalculoService;
use Illuminate\Database\Seeder;

class AforoTableSeeder extends Seeder
{
    public function run(): void
    {
        $depositos = Deposito::all();

        foreach ($depositos as $deposito) {
            // Eliminar registros anteriores para poder correr el seeder múltiples veces
            Aforo::where('deposito_id', $deposito->id)->delete();

            $diametro = $deposito->diametro;
            $longitud = $deposito->longitud;

            // Iterar desde 0 cm hasta el diámetro del tanque, en pasos de 0.5 cm
            for ($h = 0.0; $h <= $diametro; $h += 0.5) {

                $volumen = AforoCalculoService::calcularVolumenTeorico(
                    $diametro,
                    $longitud,
                    $h
                );

                Aforo::create([
                    'deposito_id' => $deposito->id,
                    'profundidad_cm' => $h,
                    'litros' => $volumen,
                ]);
            }
        }
    }
}