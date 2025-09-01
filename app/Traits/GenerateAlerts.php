<?php

namespace App\Traits;

use App\Models\Alerta;
use Illuminate\Support\Facades\Log;

trait GenerateAlerts
{
    /**
     * Genera y guarda una nueva alerta en la base de datos.
     *
     * @param array $data Los datos de la alerta. Debe contener id_rel, observacion, accion,
     * y opcionalmente id_usuario.
     * @return \App\Models\Alerta|null
     */
    public static function createAlert(array $data)
    {
        // Se establecen los valores por defecto si no son proporcionados.
        $data['dias'] = $data['dias'] ?? 0;
        $data['fecha'] = $data['fecha'] ?? now();
        $data['estatus'] = $data['estatus'] ?? 0;

        try {
            // Crea y guarda la alerta.
            $alerta = Alerta::create($data);
            return $alerta;
        } catch (\Exception $e) {
            // Registra cualquier error en la creación de la alerta.
            Log::error("Error al crear alerta: " . $e->getMessage(), $data);
            return null;
        }
    }
}
