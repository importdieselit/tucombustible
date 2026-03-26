<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Guarda o actualiza la suscripción Push del usuario actual.
     */
    public function subscribe(Request $request)
    {
        // 1. Validar los datos que vienen del JS (Axios)
        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $key      = $request->keys['p256dh'];
        $token    = $request->keys['auth'];

        // 2. Usar el método mágico del Trait HasPushSubscriptions
        // Esto inserta o actualiza en la tabla 'push_subscriptions'
        Auth::user()->updatePushSubscription($endpoint, $key, $token);

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo vinculado a TuCombustible correctamente.'
        ]);
    }
}
