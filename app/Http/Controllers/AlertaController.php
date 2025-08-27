<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertaController extends Controller
{
    /**
     * Muestra las alertas para el usuario autenticado.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Obtiene las alertas no leídas del usuario o las globales.
        $alertas = Alerta::where('estatus', 0)
                        ->where(function ($query) use ($userId) {
                            $query->where('id_usuario', $userId)
                                  ->orWhereNull('id_usuario');
                        })
                        ->orderBy('fecha', 'desc')
                        ->get();
        
        return view('alertas.index', compact('alertas'));
    }

    /**
     * Marca una alerta como leída.
     */
    public function markAsRead($id)
    {
        $alerta = Alerta::findOrFail($id);
        $alerta->estatus = 1;
        $alerta->save();

        // Redirige a la acción o ruta definida en la alerta.
        return redirect()->to($alerta->accion);
    }
}
