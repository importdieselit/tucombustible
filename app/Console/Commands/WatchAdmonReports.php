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

        // 1. Verificamos si el archivo de HOY ya fue procesado con éxito
        if (ProcessedFile::where('report_date', $todayDate)->exists()) {
            $this->info("El reporte de hoy ({$todayDateText}) ya fue procesado exitosamente. No se requiere acción.");
            return 0; // Termina la ejecución
        }

        // 2. Verificamos si el archivo esperado ya fue depositado en la carpeta
        if (Storage::exists($filePath)) {
            $this->info("Archivo {$expectedFileName} encontrado. Iniciando procesamiento...");
            $this->processFile($filePath, $expectedFileName, $todayDate);
            return 0;
        }

        // 3. El archivo no existe. Revisamos si llegamos a la hora límite (17:00)
        $currentTime = Carbon::now('America/Caracas')->format('H:i');
        
        if ($currentTime >= '17:00') {
            $this->error("Hora límite alcanzada ({$currentTime}). El archivo {$expectedFileName} no existe.");
            $this->sendWhatsAppAlert($todayDate);
            return 1;
        }

        // Si no existe pero aún no son las 17:00, simplemente esperamos al próximo ciclo
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

}