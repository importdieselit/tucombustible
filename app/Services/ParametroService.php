<?php

namespace App\Services;

use App\Models\Parametro;
use Illuminate\Support\Facades\Cache;

class ParametroService
{
    /**
     * Obtiene todos los parámetros agrupados por 'tab' o 'grupo' para las pestañas de la vista.
     */
    public function obtenerAgrupadosParaVista()
    {
        $parametros = Parametro::where('activo', true)->get();

        return $parametros->groupBy(function ($param) {
            return $param->esquema['tab'] ?? $param->grupo;
        });
    }

    /**
     * Extrae un valor específico del sistema desde la caché.
     * Uso en código: app(ParametroService::class)->get('PRECIO_MGO')
     */
    public function get(string $clave, $default = null)
    {
        $parametros = Cache::rememberForever('sistema_parametros', function () {
            return Parametro::where('activo', true)->pluck('valor', 'clave')->toArray();
        });

        if (!array_key_exists($clave, $parametros)) {
            return $default;
        }

        return $parametros[$clave]['content'] ?? $default;
    }

    /**
     * Actualiza masivamente los valores preservando la llave 'schema'.
     */
    public function actualizarMasivo(array $datosRecibidos)
    {
        foreach ($datosRecibidos as $id => $nuevoContenido) {
            $parametro = Parametro::find($id);
            
            if ($parametro) {
                $valorActual = $parametro->valor ?? [];
                
                // Preservamos la llave 'schema' y actualizamos únicamente el 'content'
                $valorActual['content'] = $nuevoContenido;
                
                $parametro->valor = $valorActual;
                $parametro->save();
            }
        }

        // Limpiar la caché tras actualizar
        Cache::forget('sistema_parametros');
    }
}