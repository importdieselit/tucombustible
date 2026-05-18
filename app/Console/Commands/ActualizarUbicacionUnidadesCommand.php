<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Service24GPSService;
use App\Models\HistorialGpsVehiculo;
use App\Models\Vehiculo;
use Carbon\Carbon;

class ActualizarUbicacionUnidadesCommand extends Command
{
    protected $signature = 'gps:actualizar';
    protected $description = 'Actualiza coordenadas de unidades y registra historial por hora';

    public function handle(Service24GPSService $gpsService)
    {
        $latSede = 10.488249123497356;
        $lngSede = -66.8234169941792;
        $radioSede = 0.180; // 180 metros en km

        try {
            $data = $gpsService->getData();
            $unidades = $data['data'] ?? [];
            
            if (empty($unidades)) {
                $this->warn('Atención: La API respondió pero el array de unidades está vacío.');
                return;
            }

            foreach ($unidades as $unidadApi) {
                $placa = $unidadApi['UnitPlate']; 
                $lat = $unidadApi['Latitude'];
                $lng = $unidadApi['Longitude'];
                
                $vehiculo = Vehiculo::where('placa', $placa)->first();
                
                if ($vehiculo) {
                    $estatus = $vehiculo->estatus;
                    $distanciaDelta = 0;

                    if (!is_null($vehiculo->latitud) && !is_null($vehiculo->longitud)) {
                        $distanciaDelta = $this->calcularDistancia($vehiculo->latitud, $vehiculo->longitud, $lat, $lng);
                        if ($distanciaDelta < 0.05) {
                            $distanciaDelta = 0;
                        }
                    }

                    $distancia = $this->calcularDistancia($latSede, $lngSede, $lat, $lng);

                    // Evaluación de límites de geocerca pura y dura
                    if (in_array($vehiculo->estatus, [1, 2])) {
                        if ($distancia > $radioSede && $vehiculo->estatus == 1) {
                            $estatus = 2; // Intenta salir
                        } elseif ($distancia <= $radioSede && $vehiculo->estatus == 2) {
                            $estatus = 1; // Intenta entrar
                        }
                    }

                    // Modificación de telemetría y contadores
                    $vehiculo->latitud = $lat;
                    $vehiculo->longitud = $lng;
                    $vehiculo->estatus = $estatus; // El guard del Observer puede sobreescribir este valor si es un rebote
                    $vehiculo->kilometraje += $distanciaDelta;
                    $vehiculo->km_contador += $distanciaDelta;
                    $vehiculo->km_mantt += $distanciaDelta;

                    // Procesar Cisterna Acoplada si aplica
                    if ($vehiculo->tipo_id == 3 && !is_null($vehiculo->acoplado_id)) {
                        $cisterna = Vehiculo::find($vehiculo->acoplado_id);
                        if ($cisterna) {
                            $cisterna->estatus = $vehiculo->estatus; // Sincroniza con lo dictaminado final por el vehiculo
                            $cisterna->latitud = $lat;
                            $cisterna->longitud = $lng;
                            $cisterna->kilometraje += $distanciaDelta; 
                            $cisterna->km_contador += $distanciaDelta;
                            $cisterna->km_mantt += $distanciaDelta;
                            $cisterna->save();
                            
                            if ($distancia > $radioSede || $vehiculo->estatus == 2) {
                                $this->actualizarHistorialPorHora($cisterna->id, $lat, $lng, $distanciaDelta);
                            }
                        }
                    }

                    // Al hacer ->save(), se disparará el Observer de forma limpia con los datos listos
                    $vehiculo->save();
              
                    if ($distancia > $radioSede || $vehiculo->estatus == 2) {
                        $this->actualizarHistorialPorHora($vehiculo->id, $lat, $lng, $distanciaDelta);
                    }
                }
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error('ERROR DE CONEXIÓN: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->error('ERROR INESPERADO: ' . $e->getMessage());
        }
    }

    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
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

        $historial = HistorialGpsVehiculo::firstOrCreate(
            ['vehiculo_id' => $vehiculoId, 'created_at' => $inicioHora],
            ['latitud' => $lat, 'longitud' => $lng, 'distancia' => 0]
        );

        $historial->increment('distancia', $delta, [
            'latitud'    => $lat, 
            'longitud'   => $lng,
            'updated_at' => Carbon::now()
        ]);
    }
}