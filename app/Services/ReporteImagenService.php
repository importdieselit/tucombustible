<?php
namespace App\Services;

use GPBMetadata\Google\Api\Log;
use Spatie\Browsershot\Browsershot;

class ReporteImagenService 
{
    public function generarSnapshotReporteOperaciones()
    {

        $token = config('services.reporte.internal_token');
        // Construimos la URL con el token
        $url = route('reporte.operaciones.interno') . "?token=" . $token;
        // $path = storage_path('app/public/reportes/operaciones_' . date('Y-m-d') . '.png');
        // $nodePath = '~/nodevenv/public_html/tucombustible/10/bin/node'; // REEMPLAZA con lo que te dio 'which node'
        // $npmPath = '~/nodevenv/public_html/tucombustible/10/bin/npm';
        // // Browsershot carga la URL de tu reporte o un HTML directo
        // Browsershot::url($url) // Una ruta optimizada para impresión
        //     ->setNodeBinary($nodePath)
        //     ->setNpmBinary($npmPath)
        //     ->noSandbox() // Evita problemas de permisos en Windows
        //     ->ignoreHttpsErrors()
        //     ->waitUntilNetworkIdle() // Espera a que carguen todas las peticiones (gráficos, css)
        //     ->waitForSelector('#reporteOperaciones') // Espera explícitamente a que aparezca el ID
        //     ->select('#reporteOperaciones')
        //     ->windowSize(1200, 800)
        //     ->setOption('args', ['--disable-web-security']) // Ayuda si hay bloqueos de CORS
        //     ->save($path);'

        $myAccessKey = 'b0b040fb73add2438ed72e257208bf44'; 
Log::info("Generando snapshot con ScreenshotLayer para URL: " . $url);
    $params = http_build_query([
        'access_key' => $myAccessKey,
        'url'        => $url,
        'viewport'   => '1200x800', // Tamaño de la ventana del navegador
        'width'      => '1200',     // Tamaño de la imagen final
        'format'     => 'PNG',
        'delay'      => 5           // Segundos que espera para que carguen los gráficos
    ]);
Log::info("Parámetros para ScreenshotLayer: " . $params);
    $apiUrl = "https://api.screenshotlayer.com/api/capture?{$params}";
    Log::info("URL completa para ScreenshotLayer: " . $apiUrl);
    $path = storage_path('app/public/reportes/operaciones_' . date('Y_m_d') . '.png');
Log::info("Ruta local donde se guardará la imagen: " . $path);
    // Usamos el cliente HTTP de Laravel que es más robusto en servidores
    $response = \Illuminate\Support\Facades\Http::get($apiUrl);
Log::info("Respuesta de ScreenshotLayer: HTTP " . $response->status());
    if ($response->successful()) {
        // Guardamos el cuerpo de la respuesta (la imagen real)
        file_put_contents($path, $response->body());
        
        // VALIDACIÓN: Si el archivo es muy pequeño, la API devolvió un error de texto
        if (filesize($path) < 2000) {
            $errorMsg = file_get_contents($path);
            \Log::error("ScreenshotLayer devolvió un error: " . $errorMsg);
            return null;
        }
        
        return $path;
    
    }
    }
}