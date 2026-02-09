<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Añade aquí la ruta exacta de tu Webhook de Telegram
        '/telegram/webhook', 
        
        // Si usaste la solución de WhatsApp, añádela también
        '/whatsapp/webhook', 
    ];
}
