<?php
namespace App\Traits;

use App\Models\Depositos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


trait CalcularAforo
{
    /**
     * Aplica el filtro de jerarquía de cliente al Query Builder.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */

    private function calcularLitros($deposito, $h_cm) 
    {
        $L = $deposito->longitud; // en metros
        $A = $deposito->diametro / 100; // Ancho convertido a metros
        $H_total = $deposito->alto / 100; // Alto convertido a metros
        $h = $h_cm / 100; // Altura medida convertida a metros
        
        // Evitar cálculos si la altura medida supera la altura del tanque
        if ($h > $H_total) $h = $H_total;

        switch ($deposito->forma) {
            case 'R': // Rectangular
            case 'C': // Cúbico (es un caso especial de rectangular)
                // Volumen = Ancho * Altura_medida * Longitud
                $volumen_m3 = $A * $h * $L;
                break;

            case 'CH': // Cilíndrico Horizontal
                $r = $A / 2; // Aquí diámetro es el diámetro real
                $termino1 = pow($r, 2) * acos(($r - $h) / $r);
                $termino2 = ($r - $h) * sqrt(2 * $r * $h - pow($h, 2));
                $volumen_m3 = ($termino1 - $termino2) * $L;
                break;

            case 'CV': // Cilíndrico Vertical
                $r = $A / 2;
                $volumen_m3 = pi() * pow($r, 2) * $h;
                break;

            case 'OH': // Ovalado Horizontal (Aproximación Elíptica)
                $a = $A / 2; // Semieje horizontal
                $b = $H_total / 2; // Semieje vertical
                // Proyección proporcional del área de una elipse
                $r_equivalente = sqrt($a * $b);
                $h_proyectada = $h * ($A / $H_total); // Ajuste de altura según proporción
                // Reutilizamos lógica de cilindro con radio equivalente
                $r = $r_equivalente;
                $termino1 = pow($r, 2) * acos(($r - $h_proyectada) / $r);
                $termino2 = ($r - $h_proyectada) * sqrt(2 * $r * $h_proyectada - pow($h_proyectada, 2));
                $volumen_m3 = ($termino1 - $termino2) * $L;
                break;

            default:
                $volumen_m3 = 0;
        }

        return round($volumen_m3 * 1000, 2); // Retornar en Litros
    }
}