<?php

namespace App\Console\Commands;

use Illuminate\Console\Command; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\ReporteImagenService;

class SendReporteDiarioOperacionesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reporte-diario-operaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera y envía el reporte diario de operaciones a WhatsApp';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
 
    public function handle()
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        
        // 1. Configuración de los reportes a enviar
        $reportesAProcesar = [
            [
                'ruta_web' => 'reporte.operaciones.interno',
                'nombre_archivo' => 'reporte_operaciones',
                'titulo' => '📊 *Reporte de Operaciones*',
                'selector' => '#reporteOperaciones'
            ],
            [
                'ruta_web' => 'reporte.flota.interno', // Ajusta según tus rutas reales
                'nombre_archivo' => 'reporte_flota',
                'titulo' => '🚛 *Reporte de Flota*',
                'selector' => '#reporte-container'
            ],
            // [
            //     'ruta_web' => 'reporte.mantenimiento.interno',
            //     'nombre_archivo' => 'reporte_mantenimiento',
            //     'titulo' => '🔧 *Reporte de Mantenimiento*',
            //     'selector' => '#reporte-container'
            // ],
            // [
            //     'ruta_web' => 'reportes.gerencial',
            //     'nombre_archivo' => 'RESUMEN_GERENCIAL',
            //     'titulo' => '📊 *Resumen Gerencial*',
            //     'selector' => '#reporteOperaciones'
            // ]
        ];

        $this->info('Iniciando secuencia de reportes...');
        $tokenInterno = config('services.reporte.internal_token');
        $reporteService = new ReporteImagenService();

        foreach ($reportesAProcesar as $reporte) {
            try {
                $this->info("Procesando: {$reporte['nombre_archivo']}...");

                // 2. Construir URL del reporte con el token
                $urlInterna = route($reporte['ruta_web']) . "?token=" . $tokenInterno;
                
                // 3. Construir URL de ScreenshotOne (Variables estandarizadas)
                $accessKey = 'm7uxLbNHYl45Tg'; // Podrías mover esto al config o .env
                $apiUrl = "https://api.screenshotone.com/take?" . http_build_query([
                    'access_key' => $accessKey,
                    'url' => $urlInterna,
                    'format' => 'png',
                    'block_ads' => 'true',
                    'block_cookie_banners' => 'true',
                    'delay' => 2, // Un pequeño delay para asegurar carga de JS/CSS
                    'timeout' => 60,
                    'selector' => $reporte['selector'],
                    'image_quality' => 80
                ]);

                // 4. Generar la imagen localmente
                $rutaImagen = $reporteService->generarSnapshot($apiUrl, $reporte['nombre_archivo']);
                
                // 5. Preparar para WhatsApp
                $dataImagen = file_get_contents($rutaImagen);
                $base64 = base64_encode($dataImagen);
                $img_ready = "data:image/png;base64," . $base64;

                // 6. Enviar vía UltraMsg
                $baseUrl = rtrim(config('services.whatsapp.url'), '/');
                $tokenWA = config('services.whatsapp.key');
                $endpoint = "{$baseUrl}/messages/image?token={$tokenWA}";

                $response = Http::asForm()->post($endpoint, [
                    'token' => $tokenWA,
                    'to' => config('services.whatsapp.group_id'),
                    'image' => $img_ready,
                    'caption' => $reporte['titulo'] . " - " . date('d/m/Y'),
                ]);

                if ($response->successful() && ($response->json()['sent'] ?? '') == 'true') {
                    $this->info("✅ {$reporte['nombre_archivo']} enviado.");
                } else {
                    $this->error("❌ Error enviando {$reporte['nombre_archivo']}: " . $response->body());
                } 

                $responseop = Http::asForm()->post($endpoint, [
                    'token' => $tokenWA,
                    'to' => config('services.whatsapp.group_operaciones'),
                    'image' => $img_ready,
                    'caption' => $reporte['titulo'] . " - " . date('d/m/Y'),
                ]);

                if ($responseop->successful() && ($responseop->json()['sent'] ?? '') == 'true') {
                    $this->info("✅ {$reporte['nombre_archivo']} enviado.");
                } else {
                    $this->error("❌ Error enviando {$reporte['nombre_archivo']}: " . $responseop->body());
                }

                // Opcional: una pequeña pausa para no saturar la API de WhatsApp
                sleep(2);

            } catch (\Exception $e) {
                $this->error("Hubo un fallo con {$reporte['nombre_archivo']}: " . $e->getMessage());
                Log::error("Error reporte {$reporte['nombre_archivo']}: " . $e->getMessage());
            }
        }

        $this->info('Secuencia finalizada.');
    }

}
