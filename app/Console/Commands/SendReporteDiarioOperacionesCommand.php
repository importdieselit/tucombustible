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
            $reporteService = new ReporteImagenService();
            $rutaImagen = $reporteService->generarSnapshotReporteOperaciones();

            $dataImagen = file_get_contents($rutaImagen);
            $base64 = base64_encode($dataImagen);
            // 3. Añadir el prefijo que UltraMsg necesita para reconocer la extensión
            $img_ready = "data:image/png;base64," . $base64;

            // 1. Generar la imagen
            $imgService = new ReporteImagenService();

            $baseUrl = rtrim(config('services.whatsapp.url'), '/');
            // Ultramsg usa /messages/image para enviar archivos de imagen
            $token   = config('services.whatsapp.key');
    
            // Concatenamos el token a la URL
            $endpoint = "{$baseUrl}/messages/image?token={$token}";

            $response = \Illuminate\Support\Facades\Http::asForm()->post($endpoint, [
                'token'   => $token,
                'to'      => config('services.whatsapp.group_id'),
                'image'   => $img_ready,
                'caption' => "📊 *Reporte operaciones - " . date('d/m/Y') . "*",
            ]);

            // 3. DEPUREMOS LA RESPUESTA REAL
            if ($response->successful()) {
                $data = $response->json();
                
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
        }
    }
}
