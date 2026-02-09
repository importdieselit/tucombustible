<?php

namespace App\Models; // O App\Models si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;
use App\Models\Vehiculo; // Asegúrate de que el modelo Marca esté correctamente importado
use App\Models\Modelo; // Asegúrate de que el modelo Modelo esté correctamente importado
use App\Models\TipoVehiculo; // Asegúrate de que el modelo TipoVehiculo esté correctamente importado
use App\Models\User; // Asegúrate de que el modelo User esté correctamente importado


class Marca extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marcas'; // Asume que la tabla se llama 'marcas'

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // Asume que la clave primaria es 'id'

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     * Si tu tabla `marcas` tiene columnas `created_at` y `updated_at`, déjalo en `true`.
     * Si no las tiene, cámbialo a `false`.
     *
     * @var bool
     */
    public $timestamps = false; // Ajustar según si tu tabla 'marcas' tiene timestamps

    /**
     * The attributes that are mass assignable.
     * Agrega aquí los nombres de las columnas que pueden ser asignadas masivamente.
     * Ejemplo: ['nombre']
     *
     * @var array
     */
    protected $fillable = [
         'marca',
        // 'descripcion',
        // ... otras columnas de la tabla 'marcas'
    ];

    // Puedes añadir relaciones si tu tabla `marcas` tiene otras relaciones.
    // Por ejemplo, para obtener los vehículos de una marca:
    
    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'marca', 'id');
    }
    public function modelos()
    {
        return $this->hasMany(Modelo::class, 'marca', 'id');
    }
}