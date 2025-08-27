<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Asegúrate de importar el modelo User
use App\Models\Inventario; // Asegúrate de importar el modelo Inventario
use App\Models\Orden; // Asegúrate de importar el modelo Orden

class InventarioSuministro extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'inventario_suministro';

    /**
     * La clave primaria de la tabla.
     * Por defecto, Eloquent asume que la clave primaria es 'id'.
     * @var string
     */
    protected $primaryKey = 'id_inventario_suministro';

    /**
     * Indica si el ID es autoincremental.
     * @var bool
     */
    public $incrementing = true;

    /**
     * El tipo de datos de la clave primaria.
     * @var string
     */
    protected $keyType = 'bigint';

    /**
     * Los atributos que se pueden asignar masivamente.
     * Esto previene el error de Asignación Masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'estatus',
        'id_usuario',
        'nro_orden',
        'destino',
        'servicio',
        'id_auto',
        'id_inventario',
        'anulacion',
        'id_emisor',
    ];

    /**
     * Indica si el modelo debe manejar automáticamente los timestamps.
     * @var bool
     */
    public $timestamps = true;  

    /**
     * Define la relación con el modelo Usuario.
     * Una entrada de inventario_suministro pertenece a un usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id', 'id_usuario');
    }
    /**
     * Define la relación con el modelo Inventario.
     * Una entrada de inventario_suministro pertenece a un inventario.
     */
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id','id_inventario');   
    }

    /**
     * Define la relación con el modelo Orden.
     * Una entrada de inventario_suministro pertenece a una orden.
     */
    public function orden()
    {
        return $this->belongsTo(Orden::class, 'nro_orden', 'nro_orden');   
    }
    

}

