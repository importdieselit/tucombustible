<?php

use Carbon\Carbon;

if (! function_exists('str')) {
    /**
     * Get a fluent string instance at the given value.
     *
     * @param  string|null  $string
     * @return \Illuminate\Support\FluentStringable|\Illuminate\Support\Str
     */
    function str($string = null)
    {
        if (is_null($string)) {
            return new \Illuminate\Support\Str;
        }

        return \Illuminate\Support\Str::of($string);
    }
}

if (!function_exists('formatVencimiento')) {
    /**
     * Formatea una fecha de vencimiento con semaforización de colores.
     * * @param string|null $fecha
     * @return string (HTML)
     */
    function formatVencimiento($fecha)
    {
        if (!$fecha) {
            return '<span class="text-muted">N/A</span>';
        }

        try {
            $fechaVenc = Carbon::parse($fecha);
            $hoy = Carbon::now();
            
            // Calculamos la diferencia en días (negativo si ya pasó)
            $diasRestantes = $hoy->diffInDays($fechaVenc, false);

            $class = 'bg-success'; // Verde: Vigente
            $tooltip = "Vigente: " . $diasRestantes . " días restantes";

            if ($diasRestantes < 0) {
                $class = 'bg-danger'; // Rojo: Vencido
                $tooltip = "VENCIDO hace " . abs($diasRestantes) . " días";
            } elseif ($diasRestantes <= 30) {
                $class = 'bg-warning text-dark'; // Amarillo: Próximo a vencer
                $tooltip = "Atención: Vence en " . $diasRestantes . " días";
            }

            return "
                        <span  title='{$tooltip}' class='badge {$class} px-2 py-1' style='min-width: 85px; display: inline-block;'>
                            " . $fechaVenc->format('d/m/Y') . "
                        </span>
                    ";

        } catch (\Exception $e) {
            return '<span class="badge bg-secondary">Error Fecha</span>';
        }
    }
    
}