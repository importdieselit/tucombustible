<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametroLegal extends Model
{
    use HasFactory;

    protected $table = 'parametros_legales';

    protected $fillable = [
        'clave',
        'valor',
        'tipo_dato',
        'descripcion'
    ];

    /**
     * Helper estático para obtener un valor rápidamente en cualquier parte del código
     * Ej: ParametroLegal::getValor('TOPE_VACACIONES', 30);
     */
    public static function getValor($clave, $default = null)
    {
        $parametro = self::where('clave', $clave)->first();
        return $parametro ? $parametro->valor : $default;
    }
}