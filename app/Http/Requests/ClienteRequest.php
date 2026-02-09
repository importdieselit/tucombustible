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
            'nombre' => 'required|string|max:255',
            // La regla 'unique' ahora ignora el ID del cliente actual si existe.
            'rif' => 'nullable|string|max:20|unique:clientes,rif,' . $clienteId,
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'contacto' => 'nullable|string|max:20',
            // La regla 'unique' ahora ignora el ID del cliente actual si existe.
            'email' => 'nullable|email|max:255|unique:clientes,email,' . $clienteId,
        ];
    }
}
