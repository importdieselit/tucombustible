<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Service24GPSService;
use App\Models\HistorialGpsVehiculo;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Output;
use Carbon\Carbon;

class ActualizarUbicacionUnidadesCommand extends Command
{
    protected $signature = 'gps:actualizar';
    protected $description = 'Actualiza coordenadas de unidades y registra historial por hora';

    public function handle(Service24GPSService $gpsService)
{

    $latSede = 10.48834308128781;
    $lngSede = -66.82329619185627;
    $radioSede = 0.100; // 100 metros en km (aprox)
    $this->output->title('Monitoreo de Sincronización GPS');

    $this->comment('1/3 -> Solicitando Token...');
    try {
        // Esto lanzará una excepción si el timeout de 30s se cumple
        $data = $gpsService->getData();
        
        $this->comment('2/3 -> Datos recibidos. Analizando JSON...');
        
        $unidades = $data['data'] ?? [];
        if (empty($unidades)) {
            $this->warn('Atención: La API respondió pero el array de unidades está vacío.');
            return;
        }

        $this->info('3/3 -> Actualizando base de datos...');
        $bar = $this->output->createProgressBar(count($unidades));
        
        foreach ($unidades as $unidadApi) {
            $placa = $unidadApi['UnitPlate']; // Ajusta según el nombre del campo en la API
            $lat = $unidadApi['Latitude'];
            $lng = $unidadApi['Longitude'];
            $cisterna = false;
           
            // 1. Actualizar tabla principal (ubicación en tiempo real)
            $vehiculo=Vehiculo::where('placa', $placa)->first();
            $estatus = $vehiculo->estatus;

            if($vehiculo){

                $distancia = $this->calcularDistancia($latSede, $lngSede, $lat, $lng);

                // 2. LÓGICA DE AUTOMATIZACIÓN DE ESTATUS (Solo para estatus 1 y 2)
                if (in_array($vehiculo->estatus, [1, 2])) {
                    // Si está fuera de los 100m y está Disponible (1) -> Pasa a En Ruta (2)
                    if ($distancia > $radioSede && $vehiculo->estatus == 1) {
                        $estatus = 2;
                        
                    }elseif ($distancia <= $radioSede && $vehiculo->estatus == 2) {
                        $estatus = 1;            
                    }
                }
                $vehiculo->latitud = $lat;
                $vehiculo->longitud = $lng;
                $vehiculo->estatus = $estatus;
                $vehiculo->save();
                if(!is_null($vehiculo->acoplado_id)){
                    $cisterna = Vehiculo::where('id', $vehiculo->acoplado_id)->first();
                    $cisterna->estatus = $estatus;
                    $cisterna->latitud = $lat;
                    $cisterna->longitud = $lng;
                    $cisterna->save();
                }
                


                    // // 2. Registro por hora (Si ya existe registro para esa placa en esta hora, lo actualiza)
                   // Lógica de Historial por Hora
                    $inicioHora = Carbon::now()->startOfHour();
                    
                    // Usamos updateOrCreate simulado con búsqueda manual por eficiencia
                    HistorialGpsVehiculo::updateOrInsert(
                        [
                            'vehiculo_id' => $vehiculo->id,
                            'created_at'  => $inicioHora
                        ],
                        [
                            'latitud'    => $lat,
                            'longitud'   => $lng,
                            'updated_at' => Carbon::now()
                        ]
                    );
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->success();

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $this->error('ERROR DE CONEXIÓN: El servidor de GPS tardó demasiado en responder.'.$e->getMessage());
    } catch (\Exception $e) {
        $this->error('ERROR INESPERADO: ' . $e->getMessage());
    }
}

    public function success(){
        $this->comment('¡Proceso terminado con éxito!');
    }

    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la tierra en KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}