<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait PluralizaEnEspanol
{
    /**
     * Mapeo de sustantivos en singular a plural en español para casos irregulares.
     * @var array
     */
    protected static $spanishIrregulars = [
        'orden' => 'ordenes',
        'camion' => 'camiones',
        'almacen' => 'almacenes',
        // Puedes agregar otros sustantivos irregulares aquí.
    ];

    /**
     * Obtiene el plural correcto para el nombre del modelo.
     *
     * @param string $singularName
     * @return string
     */
    public function getSpanishPlural($singularName)
    {
        // Primero, revisa si el nombre del modelo está en el mapeo de irregulares.
        if (array_key_exists($singularName, self::$spanishIrregulars)) {
            return self::$spanishIrregulars[$singularName];
        }

        // Si no es irregular, usa el pluralizador por defecto.
        // Se podría agregar lógica más avanzada si es necesario.
        return Str::plural($singularName);
    }
}
