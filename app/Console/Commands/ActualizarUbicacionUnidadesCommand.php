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
                
            // 1. Actualizar tabla principal (ubicación en tiempo real)
            $vehiculo=Vehiculo::where('placa', $placa)->first();
            if($vehiculo){
                $vehiculo->latitud = $lat;
                $vehiculo->longitud = $lng;
                $vehiculo->save();

                    // // 2. Registro por hora (Si ya existe registro para esa placa en esta hora, lo actualiza)
                    // // Esto cumple con tu requerimiento de "almacenar solo un registro por hora"
                    // $horaActual = Carbon::now()->startOfHour(); // Ejemplo: 2024-05-20 14:00:00

                HistorialGpsVehiculo::updateOrCreate(
                     [
                         'vehiculo_id' => $vehiculo->id
                     ],
                     [
                         'latitud' => $lat,
                         'longitud' => $lng
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
}