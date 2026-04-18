<?php
namespace App\Services;

use Spatie\Browsershot\Browsershot;

class ReporteImagenService 
{
    public function generarSnapshotReporteOperaciones()
    {

        $token = config('reporte.internal_token');
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
        //     ->save($path);

        $params = http_build_query([
            'access_key' => $token,
            'url' => $url,
            'selector' => '#reporteOperaciones', // <--- AQUÍ ESTÁ TU DIV ESPECÍFICO
            'viewport_width' => 1200,
            'viewport_height' => 800,
            'device_scale_factor' => 2, // Para que se vea en alta resolución (Retina)
            'format' => 'png',
            'delay' => 3
        ]);

        $apiKey = "b0b040fb73add2438ed72e257208bf44"; // Regístrate gratis en screenshotlayer.com
        $apiUrl = "https://api.screenshotlayer.com/api/capture?{$params}";

        $path = storage_path('app/public/reportes/operaciones_' . date('Y-m-d') . '.png');

        try {
            // 3. Descargar la imagen generada por la API
            $content = file_get_contents($apiUrl);
            
            if ($content === false) {
                throw new \Exception("No se pudo obtener la imagen de la API externa.");
            }

            // 4. Guardar en el storage local para que el comando de WhatsApp la encuentre
            file_put_contents($path, $content);
            
            return $path;

            } catch (\Exception $e) {
            \Log::error("Error en captura externa: " . $e->getMessage());
            throw $e;
        }
    }
}