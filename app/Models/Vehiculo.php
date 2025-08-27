<?php

namespace  App\Models; // O el namespace donde tengas tus modelos, por ejemplo, App\Models si usas Laravel 7+

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Asegúrate de que el modelo User esté correctamente importado
use App\Models\Marca; // Asegúrate de que el modelo Marca esté correctamente importado
use App\Models\Modelo; // Asegúrate de que el modelo Modelo esté correctamente importado
use App\Models\TipoVehiculo; // Asegúrate de que el modelo  

class Vehiculo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehiculos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // bigint unsigned puede ser int o bigint dependiendo de cómo lo maneje Eloquent internamente.

    /**
     * Indicates if the model should be timestamped.
     * En tu tabla tienes `created_at` y `updated_at`.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_usuario',
        'estatus',
        'flota',
        'marca',
        'modelo',
        'placa',
        'tipo', // Asumiendo que es un campo separado del id_tipo_vehiculo
        'tipo_diagrama',
        'serial_motor',
        'serial_carroceria',
        'transmision',
        'color',
        'anno',
        'kilometraje',
        'sucursal',
        'ubicacion',
        'ubicacion_1',
        'poliza_numero',
        'poliza_fecha_in',
        'poliza_fecha_out',
        'agencia',
        'observacion',
        'salida_fecha',
        'salida_motivo',
        'salida_id_usuario',
        'fecha_in',
        'vol',
        'km_contador',
        'condicion',
        'km_mantt',
        'cobertura',
        'tipo_poliza',
        'id_poliza',
        'certif_reg',
        'disp',
        'carga_max',
        'fuel',
        'tipo_combustible',
        'HP',
        'CC',
        'altura',
        'ancho',
        'largo',
        'consumo',
        'oil',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id_usuario' => 'integer', // bigint unsigned
        'marca' => 'integer', // bigint unsigned
        'modelo' => 'integer', // bigint unsigned
        'estatus' => 'integer',
        'kilometraje' => 'integer',
        'sucursal' => 'integer',
        'ubicacion' => 'integer',
        'salida_id_usuario' => 'integer',
        'km_contador' => 'integer',
        'km_mantt' => 'integer',
        'HP' => 'integer',
        'CC' => 'integer',
        'vol' => 'float',
        'carga_max' => 'float',
        'fuel' => 'float',
        'cobertura' => 'float',
        'altura' => 'float',
        'ancho' => 'float',
        'largo' => 'float',
        'consumo' => 'float',
        'poliza_fecha_in' => 'date',
        'poliza_fecha_out' => 'date',
        'salida_fecha' => 'date',
        'fecha_in' => 'date',
        'facturacion_completa' => 'boolean', // Si aplicara, basado en otro contexto si no fuera booleano nativo
    ];

    // Relaciones (si es necesario y tienes los modelos correspondientes)

    /**
     * Get the user that owns the vehiculo.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id'); // Ajusta 'App\User::class' al nombre de tu modelo de Usuario/User
    }

    /**
     * Get the brand associated with the vehiculo.
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca', 'id')->first() ; // Ajusta 'App\Marca::class' al nombre de tu modelo de Marca
    }

    /**
     * Get the model associated with the vehiculo.
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'modelo', 'id')->first(); // Ajusta 'App\Modelo::class' al nombre de tu modelo de Modelo
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo', 'id'); // Ajusta 'App\TipoVehiculo::class' al nombre de tu modelo de TipoVehiculo
    }   
    
}