<?php

namespace App\Services;

use App\Models\Deposito;
use InvalidArgumentException;

class AforoCalculoService
{
    /**
     * Calcula los litros reales de combustible según el tanque y los centímetros medidos.
     */
    public function calcularLitros(Deposito $deposito, float $centimetrosMedidos): float
    {
        // 1. Validación de seguridad básica de la vara
        if ($centimetrosMedidos <= 0) {
            return 0.0;
        }

        // 2. Convertimos la medición de la vara a decímetros (dm) para obtener Litros directos
        $h = $centimetrosMedidos / 10;
        $forma = strtoupper($deposito->forma);

        switch ($forma) {
            case 'CH': // Cilíndrico Horizontal
                $D = (float) $deposito->diametro / 10;
                $L = (float) $deposito->longitud / 10;
                
                if ($h >= $D) {
                    return round(M_PI * (($D / 2) ** 2) * $L, 2); // Tanque lleno
                }
                return round($this->calcularAreaSegmentoCircular($D / 2, $h) * $L, 2);

            case 'CV': // Cilíndrico Vertical
                $D = (float) $deposito->diametro / 10;
                $alto = (float) $deposito->alto / 10;
                $R = $D / 2;

                if ($h >= $alto) { $h = $alto; } // Tope de seguridad
                return round(M_PI * ($R ** 2) * $h, 2);

            case 'OH': // Oval Horizontal (Cilindro Elíptico Horizontal)
                $D = (float) $deposito->diametro / 10;  // Eje Vertical total
                $L = (float) $deposito->longitud / 10;  // Fondo
                $W = (float) $deposito->ancho / 10;     // Eje Horizontal total
                
                if ($h >= $D) {
                    return round(M_PI * ($W / 2) * ($D / 2) * $L, 2); // Volumen elipse completo
                }
                // Escalamos el área del segmento circular usando la relación de aspecto de la elipse (W / D)
                $areaCircular = $this->calcularAreaSegmentoCircular($D / 2, $h);
                $areaEliptica = $areaCircular * ($W / $D);
                return round($areaEliptica * $L, 2);

            case 'OV': // Oval Vertical
                $L = (float) $deposito->longitud / 10;
                $W = (float) $deposito->ancho / 10;     // Eje Horizontal total
                $alto = (float) $deposito->alto / 10;   // Eje Vertical total

                if ($h >= $alto) {
                    return round(M_PI * ($W / 2) * ($alto / 2) * $L, 2);
                }
                $areaCircular = $this->calcularAreaSegmentoCircular($alto / 2, $h);
                $areaEliptica = $areaCircular * ($W / $alto);
                return round($areaEliptica * $L, 2);

            case 'R': // Rectangular / Prisma
                $L = (float) $deposito->longitud / 10;
                $W = (float) $deposito->ancho / 10;
                $alto = (float) $deposito->alto / 10;

                if ($h >= $alto) { $h = $alto; }
                return round($L * $W * $h, 2);

            case 'C': // Cúbico
                $D = (float) $deposito->diametro / 10; // Dimensión base (Lado del cubo)
                
                if ($h >= $D) { $h = $D; }
                return round($D * $D * $h, 2);

            case 'E': // Esférico
                $D = (float) $deposito->diametro / 10;
                $R = $D / 2;

                if ($h >= $D) { $h = $D; }
                // Fórmula física del casquete esférico: V = (π * h² / 3) * (3R - h)
                $volumen = (M_PI * ($h ** 2) / 3) * (3 * $R - $h);
                return round($volumen, 2);

            default:
                throw new InvalidArgumentException("La forma geométrica [{$forma}] no está registrada en el motor de cubicación.");
        }
    }

    /**
     * Helper matemático: Calcula el área de un segmento circular parcial.
     * Reutilizado para CH, OH y OV para evitar duplicar código analítico complejo.
     */
    private function calcularAreaSegmentoCircular(float $R, float $h): float
    {
        $arccos_arg = ($R - $h) / $R;
        
        // Control estricto de desbordamiento de punto flotante para evitar que acos() devuelva NaN
        $arccos_arg = max(-1.0, min(1.0, $arccos_arg));
        
        return ($R ** 2) * acos($arccos_arg) - ($R - $h) * sqrt(2 * $R * $h - ($h ** 2));
    }
}