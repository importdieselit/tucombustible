<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Obtenemos el ID del vehículo si existe en la ruta (para el método de actualización).
        $vehiculoId = $this->route('vehiculo');

        return [
            'flota' => 'required|string|max:255',
            // La regla 'unique' ahora ignora el ID del vehículo actual si existe.
            'placa' => 'required|string|max:255|unique:vehiculos,placa,' . $vehiculoId,
            'marca' => 'required|integer|exists:marcas,id',
            //'modelo' => 'required|integer|exists:modelos,id',
            //'anno' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'id_usuario' => 'nullable|integer|exists:users,id',
            'estatus' => 'nullable|string|max:255',
            'tipo'  => 'nullable|integer|exists:tipos_vehiculo,id',
            'tipo_diagrama' => 'nullable|string|max:255',
            'serial_motor' => 'nullable|string|max:255',
            'serial_carroceria' => 'nullable|string|max:255',
            'transmision' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'kilometraje' => 'nullable|numeric',
            'sucursal' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|integer',
            'ubicacion_1' => 'nullable|string|max:255',
            'poliza_numero' => 'nullable|string|max:255',
            'poliza_fecha_in' => 'nullable|date',
            'poliza_fecha_out' => 'nullable|date',
            'agencia' => 'nullable|string|max:255',
            'observacion' => 'nullable|string',
            'salida_fecha' => 'nullable|date',
            'salida_motivo' => 'nullable|string',
            'salida_id_usuario' => 'nullable|integer|exists:users,id',
            'fecha_in' => 'nullable|date',
            'vol' => 'nullable|numeric',
            'km_contador' => 'nullable|numeric',
            'condicion' => 'nullable|string|max:255',
            'km_mantt' => 'nullable|numeric',
            'cobertura' => 'nullable|numeric',
            'tipo_poliza' => 'nullable|string|max:255',
            'id_poliza' => 'nullable|integer',
            'certif_reg' => 'nullable|string|max:255',
            'disp' => 'nullable|boolean',
            'carga_max' => 'nullable|numeric',
            'fuel' => 'nullable|numeric',
            'tipo_combustible' => 'nullable|string|max:255',
            'HP' => 'nullable|integer',
            'CC' => 'nullable|integer',
            'altura' => 'nullable|numeric',
            'ancho' => 'nullable|numeric',
            'largo' => 'nullable|numeric',
            'consumo' => 'nullable|numeric',
            'oil' => 'nullable|string|max:255',
        ];
    }
}
