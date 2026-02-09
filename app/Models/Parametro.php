<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla 'parametros'.
 * Utilizado para almacenar pares clave-valor de configuración de la aplicación.
 */
class Parametro extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'parametros';

    /**
     * Los atributos que son asignables masivamente.
     * En este caso, solo 'nombre' y 'valor'.
     * 'id' y 'timestamps' se manejan automáticamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'valor',
    ];

    /**
     * Los atributos que deberían ser convertidos a tipos nativos.
     * No se necesita ninguna conversión especial para 'nombre' y 'valor' (ambos string).
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];

    // Opcional: Define un scope para buscar fácilmente por nombre
    public function scopeNombre($query, $nombre)
    {
        return $query->where('nombre', $nombre);
    }
}
