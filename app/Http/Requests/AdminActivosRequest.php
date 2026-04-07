<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminActivosRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize()
    {
        // Obtenemos el usuario que está intentando hacer la petición
        $user = $this->user();

        // Verificamos si el usuario existe y si su id_perfil es 1 (Superusuario) o 2 (Administrador)
        // Usamos in_array para que el código sea limpio y fácil de leer
        return $user && in_array($user->id_perfil, [1, 2]); 
    }

    /**
     * Reglas de validación.
     */
    public function rules()
    {
        return [
            // Validamos el ID del cliente al que le asignamos los activos
            'cliente_id' => 'required|exists:clientes,id',

            // Validación de Placas (Array)
            'placas'   => 'nullable|array',
            'placas.*' => 'required|string|max:8|unique:placas_vehiculos,placa',

            // Validación de Chóferes (Array de objetos)
            'choferes'                  => 'nullable|array',
            'choferes.*.nombre_completo' => 'required|string|min:3|max:255',
            'choferes.*.cedula'          => 'required|integer|digits_between:1,8|unique:choferes_clientes,cedula',
        ];
    }

    /**
     * Mensajes personalizados para que el Admin entienda qué falló.
     */
    public function messages()
    {
        return [
            'placas.*.unique'           => 'Una de las placas ya está registrada en el sistema.',
            'placas.*.max'              => 'La placa no puede tener más de 8 caracteres.',
            'choferes.*.cedula.digits_between' => 'La cédula debe ser un número de máximo 8 dígitos.',
            'choferes.*.cedula.unique'   => 'Este número de cédula ya pertenece a un chófer registrado.',
            'choferes.*.nombre_completo.required' => 'El nombre del chófer es obligatorio.',
        ];
    }
}
