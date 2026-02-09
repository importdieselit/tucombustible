<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marca;
use App\Models\Modelo; // Asegúrate de que el modelo Modelo esté correctamente importado

class MarcaController extends BaseController
{
    // Métodos para el recurso (index, create, store, etc.) pueden ir aquí
    
    /**
     * Devuelve una lista de modelos en formato JSON para una marca dada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModelos(Request $request)
    {
        // Se valida que la solicitud contenga el ID de la marca
        $request->validate([
            'marca_id' => 'required|exists:marcas,id',
        ]);

        $marcaId = $request->input('marca_id');
        
        // Busca todos los modelos que pertenecen a la marca seleccionada
        $modelos = Modelo::where('id_marca', $marcaId)->pluck('modelo', 'id');
        
        // Retorna los modelos como una respuesta JSON
        return response()->json($modelos);
    }
}
