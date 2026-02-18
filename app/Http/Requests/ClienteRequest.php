<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que aplican a la petición.
     *
     * @return array
     */
    public function rules()
    {
        // Obtenemos el ID del cliente si existe en la ruta (para el método de actualización).
        $clienteId = $this->route('cliente');

        return [
            'nombre' => 'required|string|min:3|max:100', // Razón Social obligatoria
            'rif_tipo' => 'required|in:J,G,V,E',           // Para la matriz concatenada
            'rif_num'  => 'required|numeric',              // El número del RIF
            'telefono' => 'required|numeric|digits_between:10,20', // Campo numérico
            'contacto' => 'required|string|max:100',       // Persona de contacto obligatoria
            'email'    => 'nullable|email|unique:clientes,email,' . $clienteId,
            'direccion'=> 'required|string|max:255',
        ];
    }
}
