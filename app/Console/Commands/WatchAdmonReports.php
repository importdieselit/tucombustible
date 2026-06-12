<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\ProcessedFile;
use App\Models\ReportRecord;
use App\Events\ReportProcessedEvent;
use Carbon\Carbon;
use App\Services\WhatsappApiService; // Importamos tu servicio
use App\Services\ReporteImagenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class WatchAdmonReports extends Command
{
    protected $signature = 'reports:watch';
    protected $description = 'Monitorea la carpeta Admon_reports en un rango de tiempo y procesa el CSV del día, alertando si falla.';

    protected $whatsappService;

    /**
     * Inyectamos WhatsappApiService en el constructor
     */
    public function __construct(WhatsappApiService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
        
    }

    public function handle()
    {
        // Tomamos la fecha actual para construir el nombre esperado del archivo
        $todayDate = Carbon::now('America/Caracas')->format('Y-d-m');
        $todayDateText = Carbon::now('America/Caracas')->format('Y-m-d');
        $expectedFileName = "Resumen_Diario_DID_{$todayDate}.csv";
        $directory = 'Reportes_Admon';
        $filePath = "{$directory}/{$expectedFileName}";
        $this->info($filePath);
        // 1. Verificamos si el archivo de HOY ya fue procesado con éxito
        if (ProcessedFile::where('report_date', $todayDate)->exists()) {
            $this->info("El reporte de hoy ({$todayDateText}) ya fue procesado exitosamente. No se requiere acción.");
            return 0; // Termina la ejecución
        }

        // 2. Verificamos si el archivo esperado ya fue depositado en la carpeta
        if (Storage::exists($filePath)) {
            $this->info("Archivo {$expectedFileName} encontrado. Iniciando procesamiento...");
            $this->processFile($filePath, $expectedFileName, $todayDate);
            $this->sendWhatsappReport(); // Enviamos el reporte a WhatsApp después de procesar
            return 0;
        }

        // 3. El archivo no existe. Revisamos si llegamos a la hora límite (17:00)
        $currentTime = Carbon::now('America/Caracas')->format('H:i');
        
        if ($currentTime >= '19:31') {
            $this->error("Hora límite alcanzada ({$currentTime}). El archivo {$expectedFileName} no existe.");
            $this->sendWhatsAppAlert($todayDate);
            return 1;
        }

        // Si no existe pero aún no son las 19:30, simplemente esperamos al próximo ciclo
        $this->info("El archivo aún no se ha generado. Hora actual: {$currentTime}. Siguiente intento en 5 min.");
        return 0;
    }

    /**
     * Procesa la lectura del CSV y guarda en la base de datos
     */
    private function processFile($filePath, $fileName, $reportDate)
    {
        DB::transaction(function () use ($filePath, $fileName, $reportDate) {
            $processedFile = ProcessedFile::create([
                'file_name' => $fileName,
                'report_date' => $reportDate
            ]);

            $fileStream = Storage::readStream($filePath);
            $isHeader = true;

            while (($row = fgetcsv($fileStream, 1000, ";")) !== FALSE) {
                if ($isHeader) { $isHeader = false; continue; }
                if (empty($row[0]) || str_contains($row[0], '---')) continue;

                ReportRecord::create([
                    'processed_file_id' => $processedFile->id,
                    'report_date'       => $reportDate,
                    'tipo'              => trim($row[0]),
                    'cuenta'            => trim($row[1]),
                    'descuenta'         => trim($row[2] ?? null),
                    'monto'             => floatval($row[3] ?? 0),
                    'campo1'            => trim($row[4] ?? null),
                    'tipo_oper'         => trim($row[5] ?? null),
                    'orden'             => isset($row[6]) ? intval($row[6]) : null,
                    'reng'              => isset($row[7]) ? intval($row[7]) : null,
                ]);
            }
            fclose($fileStream);

            // Disparamos el evento por si el sistema necesita hacer algo más tras guardar
            event(new ReportProcessedEvent($processedFile));
        });
        $this->info("Archivo procesado y guardado en la base de datos correctamente.");
    }
    
    private function sendWhatsAppAlert($date)
    {
        // Construimos el mensaje de alerta
        $message = "⚠️ *ALERTA GERENCIAL* ⚠️\n\nEl reporte de cierre `Resumen_Diario_DID_{$date}.csv` no ha sido depositado en el servidor al corte de las 17:00 hrs.\n\nPor favor, verificar emisión desde Profit Plus."; // Puedes personalizar este mensaje según tus necesidades
        
        $response = $this->whatsappService->enviarMensaje($message);
        // Verificamos la respuesta        
        if ($response && $response->successful()) {
            $this->info("Alerta de WhatsApp enviada con éxito.");
        } else {
            $this->error("Fallo al enviar la alerta de WhatsApp.");
        }
    }


    public function sendWhatsappReport()
    {

        $tokenInterno = config('services.reporte.internal_token');
        $reporteService = new ReporteImagenService();
         try {
                $this->info("Procesando: Reporte Finanzas...");

                // 2. Construir URL del reporte con el token
                $urlInterna = route('reporte.admon') . "?token=" . $tokenInterno;
                
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
                    'selector' => '#reporteFinanzas',
                    'image_quality' => 80
                ]);

                // 4. Generar la imagen localmente
                $rutaImagen = $reporteService->generarSnapshot($apiUrl, 'reporte_finanzas');
                
                // 5. Preparar para WhatsApp
                $dataImagen = file_get_contents($rutaImagen);
                $base64 = base64_encode($dataImagen);
                $img_ready = "data:image/png;base64," . $base64;

                // 6. Enviar vía UltraMsg
                $baseUrl = rtrim(config('services.whatsapp.url'), '/');
                $tokenWA = config('services.whatsapp.key');
                $endpoint = "{$baseUrl}/messages/image?token={$tokenWA}";

                $numerosDestino = [
                    '584241666291', // Jefe
                    '584241177910', // Gerente
                    '584242982588', // admin
                    '584242542791', // admin2
                    '584143779488', //comercial
                    '584141780355'  // Leudo
                ];

                foreach ($numerosDestino as $numero) {
                    // Formateamos el ID para chat privado: número + @c.us
                    $idPrivado = $numero . '@c.us';

                    // Usamos tu servicio existente. 
                    // Como tu método recibe $idDestino como tercer parámetro, lo sobreescribimos aquí:

                    $response = $this->whatsappService->enviarImagen(
                        $caption = "📊 *Reporte de Finanzas* - " . date('d/m/Y'),
                        $rutaImagen = $img_ready,
                        $idDestino = $idPrivado
                    );
                    if ($response->successful() && ($response->json()['sent'] ?? '') == 'true') {
                        $this->info("✅ Reporte de Finanzas enviado.");
                    } else {
                        $this->error("❌ Error enviando Reporte de Finanzas: " . $response->body());
                    } 
                }

               

            } catch (\Exception $e) {
                $this->error("Hubo un fallo con el Reporte de Finanzas: " . $e->getMessage());
                Log::error("Error reporte de Finanzas: " . $e->getMessage());
            }
        
        return response()->json(['success' => false], 500);
    }

}