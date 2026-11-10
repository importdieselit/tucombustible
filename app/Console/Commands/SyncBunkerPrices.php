<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\PrecioBunkerHistorico;
use Carbon\Carbon;

class SyncBunkerPrices extends Command
{
    protected $signature = 'bunker:sync';
    protected $description = 'Raspa los precios de Ship & Bunker y los guarda en el histórico';

    public function handle()
    {
        $this->info('Iniciando extracción dinámica de datos de Ship & Bunker...');
        
        $url = 'https://shipandbunker.com/prices/apac/sea/sg-sin-singapore';

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->get($url);

            if (!$response->successful()) {
                $this->error("El servidor respondió con un error: " . $response->status());
                return Command::FAILURE;
            }

            $crawler = new Crawler($response->body());
            $tabla = $crawler->filter('table.port-prices')->first();

            if ($tabla->count() === 0) {
                $this->warn('No se encontró la tabla de precios.');
                return Command::FAILURE;
            }

            // 1. Extraemos los encabezados de la tabla dinámicamente
            $encabezados = $tabla->filter('thead th')->each(function (Crawler $node) {
                return trim($node->text());
            });

            // 2. Apuntamos a la primera fila de datos
            $primeraFilaTds = $tabla->filter('tbody tr')->first()->filter('td');
            $hoy = \Carbon\Carbon::now()->toDateString();
            $datosGuardados = [];

            // 3. Iteramos desde la columna 1 (Saltamos la 0 que es 'Date')
            for ($i = 1; $i < count($encabezados); $i++) {
                $combustible = strtoupper($encabezados[$i]);
                if($combustible=='HSFO') $combustible='IFO 380';
                
                // Filtramos columnas que no son combustibles reales (como el 'Scrubber Spread')
                if (str_contains(strtolower($combustible), 'spread')) {
                    continue;
                }

                $precioText = $primeraFilaTds->eq($i)->filter('.price-val')->text('0');
                $precio = (float) str_replace(',', '', $precioText);

                if ($precio > 0) {
                    // Guardamos un registro individual por cada combustible encontrado
                    PrecioBunkerHistorico::updateOrCreate(
                        [
                            'fecha' => $hoy, 
                            'combustible' => $combustible
                        ],
                        [
                            'precio' => $precio
                        ]
                    );

                    $datosGuardados[] = [$combustible, $precio];
                }
            }

            $this->info("Precios históricos para el {$hoy} guardados con éxito de forma dinámica.");
            $this->table(['Combustible', 'Precio (USD/MT)'], $datosGuardados);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error de extracción: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}