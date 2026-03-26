<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasPushSubscriptions;
use App\Notifications\BienvenidaPush;
use App\Models\User;



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

        $user = auth()->user();
        $user->updatePushSubscription($endpoint, $key, $token);

        // 2. Enviar la bienvenida de inmediato
        $user->notify(new BienvenidaPush());

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo vinculado a TuCombustible correctamente.'
        ]);
    }
}
