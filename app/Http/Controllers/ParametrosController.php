<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de Parametros.
 * Se enfoca principalmente en la recuperación de datos (solo lectura).
 */
class ParametroController extends Controller
{
    /**
     * Muestra una lista de todos los parámetros.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Recupera todos los parámetros de la base de datos
        $parametros = Parametro::all();

        // Devuelve una respuesta JSON con el estado 200 (OK)
        return response()->json([
            'message' => 'Lista de parámetros recuperada con éxito.',
            'data' => $parametros
        ], 200);
    }

    /**
     * Muestra un parámetro específico por su ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Busca el parámetro por ID o falla con un 404 si no existe
        $parametro = Parametro::find($id);

        if (!$parametro) {
            return response()->json([
                'message' => 'Parámetro no encontrado.'
            ], 404);
        }

        // Devuelve el parámetro específico
        return response()->json([
            'message' => 'Parámetro recuperado con éxito.',
            'data' => $parametro
        ], 200);
    }

    /*
     * NOTA: Para una tabla de configuración como 'parametros',
     * los métodos 'store', 'update' y 'destroy' a menudo se gestionan
     * internamente o a través de paneles de administración y se omiten
     * en APIs públicas por motivos de seguridad y estabilidad.
     *
     * Si necesitas estas funcionalidades (crear/actualizar/eliminar), avísame
     * para añadirlas.
     */
}
