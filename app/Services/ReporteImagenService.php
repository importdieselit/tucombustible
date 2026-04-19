<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ReporteImagenService 
{
    public function generarSnapshot($apiUrl, $nombreBaseArchivo)
    {
        $path = storage_path("app/public/reportes/{$nombreBaseArchivo}_" . date('Y_m_d') . ".png");
        
        Log::info("Generando reporte: {$nombreBaseArchivo} en {$path}");

        $response = Http::timeout(60)
            ->withoutVerifying() 
            ->get($apiUrl);

        if ($response->successful()) {
            file_put_contents($path, $response->body());
            
            if (filesize($path) < 3000) {
                $errorContenido = file_get_contents($path);
                throw new \Exception("La API no devolvió una imagen válida para {$nombreBaseArchivo}. Respuesta: " . $errorContenido);
            }
            
            return $path;
        } else {
            $errorExacto = $response->body();
            throw new \Exception("Error en API de captura para {$nombreBaseArchivo} (Status {$response->status()}). Razón: " . $errorExacto);
        }
    }
}