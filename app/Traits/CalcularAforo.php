<?php
namespace App\Traits;

use App\Models\Depositos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait CalcularAforo
{
    public function calcularLitros($deposito, $h_cm) 
    {
        // 1. Convertimos todo a METROS desde el inicio
        $L = (float) $deposito->longitud; 
        $diametro_m = (float) ($deposito->diametro / 100); 
        $alto_m = (float) ($deposito->alto / 100); 
        $h = (float) ($h_cm / 100); 

        if ($h <= 0 || $L <= 0) return 0.00;

        // Limitar altura al máximo del tanque
        $h_max = ($deposito->forma == 'R' || $deposito->forma == 'CV') ? $alto_m : $diametro_m;
        if ($h > $h_max) $h = $h_max;

        switch ($deposito->forma) {
            case 'R': // RECTANGULAR
                // Volumen = Ancho (diametro) * Largo * Altura medida
                // Ejemplo: 2.60m * 11.78m * 0.50m = 15.314 m3 = 15,314 L
                $volumen_m3 = $diametro_m * $L * $h;
                break;

            case 'CH': // CILINDRICO HORIZONTAL
                $r = $diametro_m / 2;
                
                // Evitar errores numéricos si h es muy cercano a 0 o al diámetro
                if ($h >= $diametro_m) return round(pi() * pow($r, 2) * $L * 10, 2);

                $parte1 = pow($r, 2) * acos(($r - $h) / $r);
                $parte2 = ($r - $h) * sqrt(2 * $r * $h - pow($h, 2));
                $volumen_m3 = ($parte1 - $parte2) * $L;
                break;

            case 'CV': // CILINDRICO VERTICAL
                $r = $diametro_m / 2;
                $volumen_m3 = pi() * pow($r, 2) * $h;
                break;

            default:
                $volumen_m3 = 0;
        }

        // 1 m3 = 1000 Litros
        return round($volumen_m3 * 10, 2);
    }
}