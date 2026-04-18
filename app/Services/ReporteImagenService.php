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
        $path = storage_path('app/public/reportes/operaciones_' . date('Y-m-d') . '.png');
        
        // Browsershot carga la URL de tu reporte o un HTML directo
        Browsershot::url($url) // Una ruta optimizada para impresión
            ->noSandbox() // Evita problemas de permisos en Windows
            ->ignoreHttpsErrors()
            ->waitUntilNetworkIdle() // Espera a que carguen todas las peticiones (gráficos, css)
            ->waitForSelector('#reporteOperaciones') // Espera explícitamente a que aparezca el ID
            ->select('#reporteOperaciones')
            ->windowSize(1200, 800)
            ->setOption('args', ['--disable-web-security']) // Ayuda si hay bloqueos de CORS
            ->save($path);

        return $path;
    }
}