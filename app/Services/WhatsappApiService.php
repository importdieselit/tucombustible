<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppApiService 
{
    protected string $url;
    protected string $key;
    protected string $destiny;

    public function __construct() {
        // Configuras esto en tu .env
        $this->url = config('services.whatsapp.url');
        $this->key = config('services.whatsapp.key');
        $this->destiny = config('services.whatsapp.group_id');
    }

    public function enviarMensaje(string $mensaje, string|null $idDestino) {
        $idDestino = $idDestino ?? $this->destiny;
        $endpoint = "{$this->url}/messages/chat?token={$this->key}";

        return Http::asForm()->post($endpoint, [
            'token' => $this->key,
            'to'    => $idDestino,
            'body'  => $mensaje, 
        ]);
    }

    public function enviarImagen(string $caption, string $rutaImagen, string|null $idDestino) {
        $idDestino = $idDestino ?? $this->destiny;
        $endpoint = "{$this->url}/messages/image?token={$this->key}";

        return Http::asForm()->post($endpoint, [
            'token' => $this->key,
            'to' => $idDestino,
            'image' => $rutaImagen, 
            'caption' => $caption . " - " . date('d/m/Y'),
        ]);
    }
}