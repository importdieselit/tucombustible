<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppApiService 
{
    protected $url;
    protected $key;

    public function __construct() {
        // Configuras esto en tu .env
        $this->url = config('services.whatsapp.url');
        $this->key = config('services.whatsapp.key');
    }

    public function enviarMensaje($idDestino, $mensaje) {
        return Http::withHeaders([
            'apikey' => $this->key
        ])->post("{$this->url}/message/sendText", [
            'number' => $idDestino, // Aquí va el ID del grupo o comunidad
            'text' => $mensaje,
            'linkPreview' => true
        ]);
    }

    public function enviarImagen($idDestino, $caption, $rutaImagen) {
        // Útil para enviar el gráfico del reporte de disponibilidad
        return Http::withHeaders(['apikey' => $this->key])
            ->post("{$this->url}/message/sendMedia", [
                'number' => $idDestino,
                'media' => $rutaImagen,
                'caption' => $caption
            ]);
    }
}