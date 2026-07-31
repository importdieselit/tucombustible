<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappApiService 
{
    protected $url;
    protected $key;
    protected $destiny;

    public function __construct() 
    {
        // Asegúrate de que coincidan con tu config/services.php
        $this->url = rtrim(config('services.whatsapp.url'), '/');
        $this->key = config('services.whatsapp.key');
        $this->destiny = config('services.whatsapp.group_id');
    }

    /**
     * Enviar mensaje de texto
     */
    public function enviarMensaje($mensaje, $idDestino = null) 
    {
        $target = $idDestino ?? $this->destiny;
        $endpoint = "{$this->url}/messages/chat";

        try {
            return Http::asForm()
                ->withoutVerifying() // Evita fallos de certificados SSL
                ->post($endpoint, [
                    'token' => $this->key,
                    'to'    => $target,
                    'body'  => $mensaje,
                    'priority' => 1
                ]);
        } catch (\Exception $e) {
            Log::error("Error WhatsApp EnviarMensaje: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar imagen con pie de foto
     */
    public function enviarImagen($caption, $rutaImagen, $idDestino = null) 
    {
        $target = $idDestino ?? $this->destiny;
        $endpoint = "{$this->url}/messages/image";

        try {
            return Http::asForm()
                ->withoutVerifying()
                ->post($endpoint, [
                    'token'   => $this->key,
                    'to'      => $target,
                    'image'   => $rutaImagen, 
                    'caption' => $caption,
                    'priority' => 1
                ]);
        } catch (\Exception $e) {
            Log::error("Error WhatsApp EnviarImagen: " . $e->getMessage());
            return false;
        }
    }
}