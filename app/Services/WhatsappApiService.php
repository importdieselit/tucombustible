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
        return Http::post("{$this->url}/messages/chat", [
            'token' => $this->key,
            'to'    => $idDestino,
            'body'  => $mensaje,
        ]);
    }

    public function enviarImagen($idDestino, $caption, $rutaImagen) {
        return Http::post("{$this->url}/messages/image", [
            'token'   => $this->key,
            'to'      => $idDestino,
            'image'   => $rutaImagen,
            'caption' => $caption
        ]);
    }
}