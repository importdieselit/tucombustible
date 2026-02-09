<?php 
// app/Services/AforoCalculoService.php

namespace App\Services;

class AforoCalculoService
{
    /**
     * Calcula el volumen de un cilindro horizontal parcial.
     * @param float $diametro_cm Diámetro del tanque en cm.
     * @param float $longitud_cm Longitud del tanque en cm.
     * @param float $h_cm Altura del líquido en cm.
     * @return float Volumen en Litros.
     */
    public static function calcularVolumenTeorico(float $diametro_cm, float $longitud_cm, float $h_cm): float
    {
        // 1. Convertir a Decímetros (dm) para obtener el resultado en Litros (dm^3)
        $D = $diametro_cm / 10;
        $L = $longitud_cm / 10;
        $h = $h_cm / 10;
        $R = $D / 2;

        // Si la profundidad es cero o negativa
        if ($h <= 0) {
            return 0.0;
        }

        // Si la profundidad es igual o mayor al diámetro (tanque lleno)
        if ($h >= $D) {
            // Fórmula del volumen total: V = π * R² * L
            return M_PI * ($R ** 2) * $L;
        }

        // 2. Aplicar la Fórmula del Segmento Circular (para V parcial)
        // V = L * [ R² * arccos((R-h)/R) - (R-h) * sqrt(2*R*h - h²) ]

        // El argumento de arccos debe estar entre -1 y 1
        $arccos_arg = ($R - $h) / $R;
        
        // Usamos M_PI para π y la función nativa acos para arccos
        $area_seccion = ($R ** 2) * acos($arccos_arg) - ($R - $h) * sqrt(2 * $R * $h - ($h ** 2));

        $volumen_litros = $L * $area_seccion;

        return round($volumen_litros, 2);
    }
}