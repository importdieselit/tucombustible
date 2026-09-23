<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Validaremos los permisos (ej. canAccess) en el controlador
    }

    /**
     * LÓGICA RN9: Formatear textos antes de validar
     */
    protected function prepareForValidation()
    {
        // Campos que queremos forzar a MAYÚSCULAS y 1 solo espacio
        $camposAFormatear = ['nombre', 'apellidos', 'direccion', 'nombre_contacto'];

        $input = $this->all();
        foreach ($camposAFormatear as $campo) {
            if (isset($input[$campo])) {
                // 1. Quitar espacios múltiples por uno solo
                $textoLimpio = preg_replace('/\s+/', ' ', $input[$campo]);
                // 2. Convertir a mayúsculas y quitar espacios en los extremos
                $input[$campo] = mb_strtoupper(trim($textoLimpio), 'UTF-8');
            }
        }
        $this->replace($input);
    }

    public function rules(): array
    {
        // Regla RN7: Validar campos obligatorios
        return [
            'nombre'           => 'required|string|max:50',
            'apellidos'        => 'required|string|max:50',
            'dni'              => 'required|numeric|unique:personas,dni',
            'email'            => 'required|email|max:100',
            'cargo_id'         => 'required|exists:cargo,id',
            'dependencia'      => 'required|exists:departamentos,id', // id del nuevo catalogo
            'fecha_in'         => 'required|date',
            'tipo_contrato'    => 'required|string',
            // ... (resto de las validaciones de los campos)
        ];
    }
}