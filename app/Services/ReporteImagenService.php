<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ReporteImagenService 
{
    public function generarSnapshotReporteOperaciones($apiUrl = null)
    {

        $token = config('services.reporte.internal_token');
        // Construimos la URL con el token
    Log::info("URL completa para ScreenshotLayer: " . $apiUrl);
    $path = storage_path('app/public/reportes/operaciones_' . date('Y_m_d') . '.png');
    Log::info("Ruta local donde se guardará la imagen: " . $path);
    // Usamos el cliente HTTP de Laravel que es más robusto en servidores
   $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withoutVerifying() 
                ->get($apiUrl);

    if ($response->successful()) {
        file_put_contents($path, $response->body());
        
        if (filesize($path) < 3000) {
            $errorContenido = file_get_contents($path);
            throw new \Exception("La API no devolvió una imagen. Respuesta: " . $errorContenido);
        }
        
        return $path;
    
    } else {
        // 2. CAMBIO CLAVE: Leer el JSON o texto que devuelve el error 400
        $errorExacto = $response->body();
        throw new \Exception("Error ScreenshotLayer (Status 400). Razón exacta: " . $errorExacto);
    }
    }
}