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

        $myAccessKey = 'tucombustiblepass'; 
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
    $apiUrl = "http://api.screenshotlayer.com/api/capture?{$params}";
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