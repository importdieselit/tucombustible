<?php

namespace App\Console\Commands;

use Illuminate\Console\Command; 
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
        // Silenciar avisos de compatibilidad de librerías de terceros
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        $this->info('Iniciando generación de reporte...');
        
        try {
            // 1. Generar la imagen
            $reporteService = new ReporteImagenService();
            $url = route('reporte.operaciones.interno') . "?token=" . $token;
            $apiUrl = "https://api.screenshotone.com/take?access_key=m7uxLbNHYl45Tg&url=" . urlencode($url) . "&format=jpg&block_ads=true&block_cookie_banners=true&block_banners_by_heuristics=false&block_trackers=true&delay=0&timeout=60&response_type=by_format&selector=%23reporteOperaciones&image_quality=80";

            $rutaImagen = $reporteService->generarSnapshotReporteOperaciones($apiUrl);

            if (!$rutaImagen || !file_exists($rutaImagen) || filesize($rutaImagen) < 3000) {
                 throw new \Exception("La imagen generada no existe o es inválida. Revisa los logs de ReporteImagenService.");
            }

            $this->info('Imagen generada en: ' . $rutaImagen);
            
            // 2. Preparar la imagen en Base64 con el prefijo correcto
            $dataImagen = file_get_contents($rutaImagen);
            $base64 = base64_encode($dataImagen);
            // 3. Añadir el prefijo que UltraMsg necesita para reconocer la extensión
            $img_ready = "data:image/png;base64," . $base64;
            
            // 4. Configurar envío a WhatsApp
            $baseUrl = rtrim(config('services.whatsapp.url'), '/');
            $token   = config('services.whatsapp.key');
            
            // Concatenamos el token a la URL
            $endpoint = "{$baseUrl}/messages/image?token={$token}";
            $this->info('Endpoint completo: ' . $endpoint);
            
            $response = \Illuminate\Support\Facades\Http::asForm()->post($endpoint, [
                'token'   => $token,
                'to'      => config('services.whatsapp.group_id'),
                'image'   => $img_ready, // Ahora lleva el prefijo de extensión
                'caption' => "📊 *Reporte operaciones - " . date('d/m/Y') . "*",
            ]);

            // 5. DEPUREMOS LA RESPUESTA REAL
            if ($response->successful()) {
                $data = $response->json();
                $this->info('Respuesta de la API: ' . json_encode($data));
                
                // Si la API responde OK pero trae un error interno
                if (isset($data['sent']) && $data['sent'] == 'true') {
                    $this->info('Reporte enviado correctamente al grupo.');
                } else {
                    $this->error('La API aceptó el mensaje pero NO lo envió: ' . json_encode($data));
                }
            } else {
                $this->error('Error de conexión con la API (' . $response->status() . '): ' . $response->body());
            }
            $this->info('Reporte generado y enviado exitosamente.');

        } catch (\Exception $e) {
            $this->error('Error al generar o enviar el reporte: ' . $e->getMessage());
            // Importante loguear la excepción completa para detalles
            \Log::error("Error en SendReporteDiarioOperacionesCommand: " . $e->getMessage());
        }
    }
}
