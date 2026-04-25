<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Service24GPSService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.s24.apikey');
        $this->baseUrl = config('services.s24.url');
    }

    /**
     * Obtiene el token activo de la caché o genera uno nuevo
     */
   protected function login()
    {
        // LOG para depurar el login
//        Log::info("GPS: Intentando Login para obtener nuevo token...");

        $response = Http::asForm()->post("{$this->baseUrl}/gettoken", [
            'apikey'   => $this->apiKey,
            'username' => config('services.s24.username'),
            'password' => config('services.s24.password'),
        ]);

        // REVISIÓN CRÍTICA: Verifica qué devuelve la API en el login
        if ($response->successful()) {
            $data = $response->json();
            
            // Verificamos si existe la clave 'token' dentro de 'data'
            if (isset($data['data']) && $data['status']==200) {
  //              Log::info("GPS: Token obtenido con éxito.");
                return $data['data'];
            }
        }

    //    Log::error("GPS: Falló el login. Respuesta: " . $response->body());
        throw new \Exception("No se pudo obtener el token de Service24GPS.");
    }

    public function getToken()
    {
        // Intentamos obtener de caché, si no, ejecutamos login()
        return Cache::remember('s24_token', now()->addHours(5), function () {
            return $this->login();
        });
    }

    public function getData()
    {
        $token = $this->getToken();

        // Si por alguna razón getToken devolvió null, forzamos error antes de la petición
        if (!$token) {
            throw new \Exception("El token es nulo. Revisa las credenciales de Login.");
        }

        $params = [
            'apikey'      => $this->apiKey,
            'token'       => $token,
            'UseUTCDate'  => '0',
            'sensores'    => '1'
        ];  

      //  Log::info("=== INICIO DE PETICIÓN GPS ===");
        //Log::info("URL: {$this->baseUrl}/getdata");
        
        $response = Http::asForm()->timeout(3000)->post("{$this->baseUrl}/getdata", $params);

        $status = $response->json('status');

        if ($status == 30400) {
          //  Log::warning("Token inválido (30400). Limpiando caché y reintentando...");
            Cache::forget('s24_token');
            // IMPORTANTE: Solo reintentar una vez o manejar un contador para evitar bucles infinitos
            return $this->getData(); 
        }

        return $response->json();
    }
}