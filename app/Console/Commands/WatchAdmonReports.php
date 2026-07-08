<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\ProcessedFile;
use App\Models\ReportRecord;
use App\Events\ReportProcessedEvent;
use Carbon\Carbon;
use App\Services\WhatsappApiService;
use App\Services\ReporteImagenService;
use Illuminate\Support\Facades\Log;

class WatchAdmonReports extends Command
{
    protected $signature = 'reports:watch';
    protected $description = 'Monitorea y procesa reportes administrativos segmentando por turnos independientes.';

    protected $whatsappService;

    public function __construct(WhatsappApiService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $now = Carbon::now('America/Caracas');
        $currentTime = $now->format('H:i');
        
        $todayDateText = $now->format('Y-m-d');
        $todayDateMFile = $now->format('Y-d-m');
        $todayDateFile = $now->format('d-m-Y');
        
        $directory = 'Reportes_Admon';

        // Estructura de configuración limpia y estandarizada
        $filesToWatch = [
            [
                'file_name'   => "Resumen_Diario_matutino_DID_{$todayDateMFile}.csv",
                'report_date' => $todayDateText,
                'alert_time'  => '08:30',
                'turno'       => 'Matutino'
            ],
            [
                'file_name'   => "Resumen_Diario_DID_{$todayDateFile}.csv",
                'report_date' => $todayDateText,
                'alert_time'  => '17:30',
                'turno'       => 'Vespertino'
            ]
        ];
        $this->info('iniciando revision');
        foreach ($filesToWatch as $fileConfig) {
            $expectedFileName = $fileConfig['file_name'];
            $reportDate = $fileConfig['report_date'];
            $turno = $fileConfig['turno'];
            $filePath = "{$directory}/{$expectedFileName}";
            $this->info($filePath);
            $this->info('verificando archivo ['.$expectedFileName.'] para '.$reportDate.' - '.$turno.'...');

            // CONTROL DE ERRORES 1: Evitar reprocesar por Turno y Fecha (Blindaje de duplicados)
            if (ProcessedFile::where('report_date', $reportDate)->where('turno', $turno)->exists()) {
                $this->info('ya procesado '.$turno.' para '.$reportDate.'. Continuando...  ');
                continue; 
            }

            // Si el archivo físico existe en el Storage
            if (Storage::exists($filePath)) {
                $this->info("Archivo ['.$turno.'] encontrado: {$expectedFileName}. Procesando...");
                
                // CONTROL DE ERRORES 2: Capturar excepciones de lectura del archivo
                try {
                    $this->processFile($filePath, $expectedFileName, $reportDate, $turno);
                    $this->sendWhatsappReport($reportDate, $turno);
                    $this->info('archivo procesado correctamente enviado whatsapp');
                } catch (\Exception $e) {
                    $this->error("Error crítico procesando {$expectedFileName}: " . $e->getMessage());
                    Log::critical("Fallo en procesamiento de reporte [{$turno}] de fecha {$reportDate}: " . $e->getMessage());
                    // Aquí podrías enviar un WhatsApp técnico alertándote a ti de un fallo de estructura de datos
                }
                
                continue;
            }else{
               $this->info('Archivo no encontrado: {$expectedFileName}. Continuando... ');
            }

            // Alerta por WhatsApp si llegó la hora de corte y no está el archivo
            if ($currentTime === $fileConfig['alert_time']) {
                $this->error("Hora límite alcanzada para el reporte {$turno}. Enviando alerta...");
                $this->sendWhatsAppAlert($expectedFileName, $turno);
            }

        }
        $this->info('terminando revision');
        return 0;
    }

    private function processFile($filePath, $fileName, $reportDate, $turno)
    {
        DB::transaction(function () use ($filePath, $fileName, $reportDate, $turno) {
            $processedFile = ProcessedFile::create([
                'file_name'   => $fileName,
                'report_date' => $reportDate,
                'turno'       => $turno
            ]);

            $fileStream = Storage::readStream($filePath);
            if ($fileStream === false) {
                throw new \Exception("No se pudo abrir el stream para el archivo: {$filePath}");
            }

            $isHeader = true;

            while (($row = fgetcsv($fileStream, 1000, ";")) !== FALSE) {
                if ($isHeader) { $isHeader = false; continue; }
                if (empty($row[0]) || str_contains($row[0], '---')) continue;

                // CONTROL DE ERRORES 3: Validar que la fila contenga la estructura mínima
                if (count($row) < 4) {
                    Log::warning("Fila malformada omitida en archivo {$fileName}: " . json_encode($row));
                    continue;
                }

                ReportRecord::create([
                    'processed_file_id' => $processedFile->id,
                    'report_date'       => $reportDate,
                    'turno'             => $turno, // Guardamos el turno en cada registro
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

            event(new ReportProcessedEvent($processedFile));
        });
    }
    
    private function sendWhatsAppAlert($fileName, $turno)
    {
        $message = "⚠️ *ALERTA GERENCIAL - REPORTE {$turno}* ⚠️\n\nEl archivo `{$fileName}` no ha sido depositado en el servidor a la hora límite.\n\nPor favor, verificar emisión desde Profit Plus.";
        $this->whatsappService->enviarMensaje($message);
    }

    public function sendWhatsappReport($reportDate, $turno)
    {
        $tokenInterno = config('services.reporte.internal_token');
        $reporteService = new ReporteImagenService();
        
        try {
            // CONTROL DE ERRORES 4: Enviamos el parámetro &turno a la URL interna
            $urlInterna = route('reporte.admon') . "?token=" . $tokenInterno . "&date=" . $reportDate . "&turno=" . $turno;
            
            $accessKey = 'm7uxLbNHYl45Tg'; 
            $apiUrl = "https://api.screenshotone.com/take?" . http_build_query([
                'access_key' => $accessKey,
                'url' => $urlInterna,
                'format' => 'png',
                'block_ads' => 'true',
                'block_cookie_banners' => 'true',
                'delay' => 2, 
                'timeout' => 60,
                'selector' => '#reporteFinanzas',
                'image_quality' => 80
            ]);

            $rutaImagen = $reporteService->generarSnapshot($apiUrl, "reporte_finanzas_{$reportDate}_{$turno}");
            
            $dataImagen = file_get_contents($rutaImagen);
            $base64 = base64_encode($dataImagen);
            $img_ready = "data:image/png;base64," . $base64;

            $numerosDestino = [
                '584241666291', '584241177910', 
                '584242982588', '584242542791', '584143779488', '584141780355'
            ];

            foreach ($numerosDestino as $numero) {
                $this->whatsappService->enviarImagen(
                    "📊 *Reporte de Finanzas ({$turno})* - " . Carbon::parse($reportDate)->format('d/m/Y'),
                    $img_ready,
                    $numero . '@c.us'
                );
            }

        } catch (\Exception $e) {
            Log::error("Error enviando reporte WhatsApp [{$turno}]: " . $e->getMessage());
        }
    }
}