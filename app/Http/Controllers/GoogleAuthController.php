<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;

class GoogleAuthController extends Controller
{
   

    // 1. EL BOTÓN QUE LO MANDA A GOOGLE
    public function redirectToGoogle()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('APP_URL') . '/auth/google/callback');
        
        // PERMISOS QUE VAS A EXIGIR (Agrega los de Drive y Calendar si quieres)
        $client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $client->addScope('https://www.googleapis.com/auth/calendar');
        
        // ESTO ES VITAL: Pide acceso offline para que te den el bendito Refresh Token
        $client->setAccessType('offline'); 
        $client->setPrompt('consent'); // Fuerza que le vuelva a preguntar siempre

        return redirect($client->createAuthUrl());
    }

    // 2. CUANDO GOOGLE TE LO DEVUELVE
    public function handleGoogleCallback(Request $request)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        // ... configuras igual que arriba ...

        // Cambias el código temporal que te dio Google por los Tokens de verdad
        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        // Guardas esa basura en la base de datos asociada al usuario logueado
        $user = auth()->user();
        $user->google_access_token = json_encode($token);
        
        if (isset($token['refresh_token'])) {
            $user->google_refresh_token = $token['refresh_token'];
        }
        $user->save();

        return redirect('/dashboard')->with('success', 'Cuenta de Google enlazada. C.O.R.E. ahora vigila tu alma.');
    }
}
