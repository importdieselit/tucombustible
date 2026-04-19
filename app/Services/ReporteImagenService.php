<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ReporteImagenService 
{
    public function generarSnapshotReporteOperaciones()
    {

        $token = config('services.reporte.internal_token');
        // Construimos la URL con el token
        $url = route('reporte.operaciones.interno') . "?token=" . $token;
    $apiUrl = "https://api.screenshotone.com/take?access_key=m7uxLbNHYl45Tg&url=https%3A%2F%2Ftucombustible.com.ve%2Fviajes%2Freporte-interno%3Ftoken%3DOZ8ucq4Np6yDTSJQPwKQYsXsByC4bR55N%2FVTj%2Fg9GC8%3D&format=jpg&block_ads=true&block_cookie_banners=true&block_banners_by_heuristics=false&block_trackers=true&delay=0&timeout=60&response_type=by_format&selector=%23reporteOperaciones&image_quality=80";
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