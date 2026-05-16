<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Service24GPSService;
use App\Models\HistorialGpsVehiculo;
use App\Models\Vehiculo;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Output;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActualizarUbicacionUnidadesCommand extends Command
{
    protected $signature = 'gps:actualizar';
    protected $description = 'Actualiza coordenadas de unidades y registra historial por hora';

    public function handle(Service24GPSService $gpsService)
{

    $latSede = 10.48834308128781;
    $lngSede = -66.82329619185627;
    $radioSede = 0.200; // 200 metros en km (aprox)
    $baseUrl = rtrim(config('services.whatsapp.url'), '/');
    $tokenWA = config('services.whatsapp.key');
    $endpoint = "{$baseUrl}/messages/chat?token={$tokenWA}";
    //$this->output->title('Monitoreo de Sincronización GPS');

    //$this->comment('1/3 -> Solicitando Token...');
    try {
        // Esto lanzará una excepción si el timeout de 30s se cumple
        $data = $gpsService->getData();
        
      //  $this->comment('2/3 -> Datos recibidos. Analizando JSON...');
        
        $unidades = $data['data'] ?? [];
        if (empty($unidades)) {
            $this->warn('Atención: La API respondió pero el array de unidades está vacío.');
            return;
        }

        //$this->info('3/3 -> Actualizando base de datos...');
        //  $bar = $this->output->createProgressBar(count($unidades));
        
        foreach ($unidades as $unidadApi) {
            $placa = $unidadApi['UnitPlate']; // Ajusta según el nombre del campo en la API
            $lat = $unidadApi['Latitude'];
            $lng = $unidadApi['Longitude'];
            $cisterna = false;
           
            // 1. Actualizar tabla principal (ubicación en tiempo real)
            $vehiculo=Vehiculo::where('placa', $placa)->first();
            
            if($vehiculo){
                $estatus = $vehiculo->estatus;

                $distanciaDelta = 0;
                if (!is_null($vehiculo->latitud) && !is_null($vehiculo->longitud)) {
                    $distanciaDelta = $this->calcularDistancia(
                        $vehiculo->latitud, 
                        $vehiculo->longitud, 
                        $lat, 
                        $lng
                    );
                    if ($distanciaDelta < 0.05) {
                        $distanciaDelta = 0;
                    }
                }

                $distancia = $this->calcularDistancia($latSede, $lngSede, $lat, $lng);
                $estatus = $vehiculo->estatus;
                // 2. LÓGICA DE AUTOMATIZACIÓN DE ESTATUS (Solo pa  ra estatus 1 y 2)
                if (in_array($vehiculo->estatus, [1, 2])) {
                    // Si está fuera de los 100m y está Disponible (1) -> Pasa a En Ruta (2)
                    if ($distancia > $radioSede && $vehiculo->estatus == 1) {
                        $estatus = 2;
                        $viaje = $vehiculo->viajes()->where('status', 'Programado')->first();
                        $chofer = $viaje ? $viaje->chofer()->first()->persona()->first() : null;
                        $nombre = $chofer ? $chofer->nombre.' con ' : null;
                        // --- NOTIFICACIÓN SELECTIVA WHATSAPP ---
                        $mensaje= "🚀 *SALIDA DETECTADA*: {$nombre}La Unidad {$vehiculo->flota} - {$vehiculo->placa} ha salido de la sede.";
                         // 6. Enviar vía UltraMsg
                        $response = Http::asForm()
                            ->withoutVerifying() // Equivalente a CURLOPT_SSL_VERIFYPEER => 0
                            ->post($endpoint, [
                                'token'      => $tokenWA,
                                'to'         => config('services.whatsapp.group_operaciones'),
                                'body'       => $mensaje,
                                'priority'   => 1, // Importante si lo tenías en el script original
                                'referenceId' => '',
                            ]);

                        // 3. Validación robusta
                        // UltraMsg a veces devuelve el campo 'sent' como string "true" o como booleano true
                        $resultado = $response->json();
                        $isSent = isset($resultado['sent']) && ($resultado['sent'] == 'true' || $resultado['sent'] === true);

                        if ($response->successful() && $isSent) {
                            $this->info("✅ Notificación enviada. ID: " . ($resultado['id'] ?? 'N/A'));
                        } else {
                            $this->error("❌ Error UltraMsg: " . $response->body());
                        }
                    }elseif ($distancia <= $radioSede && $vehiculo->estatus == 2) {
                        $estatus = 1;  
                        // --- NOTIFICACIÓN SELECTIVA WHATSAPP ---
                        $viaje = $vehiculo->viajes()->where('status', 'EN RUTA')->first();
                        $chofer = $viaje ? $viaje->chofer()->first()->persona()->first() : null;
                        $nombre = $chofer ? $chofer->nombre.' con ' : null;

                        $mensaje= "🏠 *RETORNO DETECTADO*: {$nombre}La unidad {$vehiculo->flota} - {$vehiculo->placa} ha ingresado a la sede.";
                        $response = Http::asForm()->withoutVerifying()->post($endpoint, [
                            'token' => $tokenWA,
                            'to' => config('services.whatsapp.group_operaciones'),
                            'body' => $mensaje,
                            'priority' => 1,
                            'referenceId' => '',
                        ]);
                        if ($response->successful() && ($response->json()['sent'] ?? '') == 'true') {
                            $this->info("✅ notificacion enviada.");
                        } else {
                            $this->error("❌ Error enviando " . $response->body());
                        }
                    }
                }
                $vehiculo->latitud = $lat;
                $vehiculo->longitud = $lng;
                $vehiculo->estatus = $estatus;
                $vehiculo->kilometraje += $distanciaDelta;
                $vehiculo->km_contador += $distanciaDelta;
                $vehiculo->km_mantt += $distanciaDelta;


                if ($vehiculo->tipo_id == 3 && !is_null($vehiculo->acoplado_id)) {
                    $cisterna = Vehiculo::find($vehiculo->acoplado_id);
                    if ($cisterna) {
                        $cisterna->estatus = $estatus;
                        $cisterna->latitud = $lat;
                        $cisterna->longitud = $lng;
                        $cisterna->kilometraje += $distanciaDelta; // La cisterna recorre lo mismo que el chuto
                        $cisterna->km_contador += $distanciaDelta;
                        $cisterna->km_mantt += $distanciaDelta;
                        $cisterna->save();
                        if($distancia > $radioSede || $vehiculo->estatus == 2){
                            $this->actualizarHistorialPorHora($cisterna->id, $lat, $lng, $distanciaDelta);
                        }
                    }
                }

                $vehiculo->save();
          
                if($distancia > $radioSede || $vehiculo->estatus == 2){
                    $this->actualizarHistorialPorHora($vehiculo->id, $lat, $lng, $distanciaDelta);
                }
            }
       //     $bar->advance();
        }
        
        //$bar->finish();
       // $this->newLine(2);
     //   $this->success();

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

    private function actualizarHistorialPorHora($vehiculoId, $lat, $lng, $delta)
    {
        $inicioHora = Carbon::now()->startOfHour();

        // Buscamos el registro de esta hora o lo creamos si no existe
        $historial = HistorialGpsVehiculo::firstOrCreate(
            ['vehiculo_id' => $vehiculoId, 'created_at' => $inicioHora],
            ['latitud' => $lat, 'longitud' => $lng, 'distancia' => 0]
        );

        // Incrementamos la distancia acumulada y actualizamos la última posición conocida
        $historial->increment('distancia', $delta, [
            'latitud' => $lat, 
            'longitud' => $lng,
            'updated_at' => Carbon::now()
        ]);
    }
}