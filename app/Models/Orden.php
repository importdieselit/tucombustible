<?php

namespace App\Models; // O App\Models
use App\Models\TipoOrden;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // La tabla 'ordenes' no tiene 'created_at' ni 'updated_at' en tu SQL

    protected $fillable = [
        'id_usuario',
        'id_taller',
        'taller_externo',
        'nro_orden',
        'tipo',
        'estatus',
        'id_vehiculo',
        'kilometraje',
        'descripcion_1',
        'descripcion_2',
        'descripcion_3',
        'descripcion',
        'id_us_in',
        'fecha_in',
        'hora_in',
        'fecha_out',
        'hora_out',
        'id_us_out',
        'id_plan',
        'facturacion_completa',
        'observacion',
        'origen',
        'anulacion',
        'nombre_usuario',
        'promesa',
        'chfr',
        'responsable',
        'parent',
        'id_vehiculo'
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_taller' => 'integer',
        'taller_externo' => 'integer',
        'nro_orden' => 'string',
        'estatus' => 'string', // Asumiendo que es varchar de tu migración anterior
        'id_auto' => 'integer',
        'kilometraje' => 'integer',
        'id_us_in' => 'integer',
        'fecha_in' => 'date',
        'hora_in' => 'string', // time type
        'fecha_out' => 'date',
        'hora_out' => 'string', // time type
        'id_us_out' => 'integer',
        'id_plan' => 'integer',
        'facturacion_completa' => 'boolean',
        'origen' => 'integer',
        'parent' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // public function taller()
    // {
    //     return $this->belongsTo(Taller::class, 'id_taller', 'id'); // Asumiendo un modelo Taller
    // }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id')->first();
    }

    public function tipo()
    {
        return $this->belongsTo(TipoOrden::class, 'tipo', 'id_tipo_orden')->first();
    }

    public function usuarioInicio()
    {
        return $this->belongsTo(User::class, 'id_us_in', 'id');
    }

    public function usuarioCierre()
    {
        return $this->belongsTo(User::class, 'id_us_out', 'id');
    }

    public function estatus()
    {
        return $this->belongsTo(EstatusData::class, 'estatus', 'id_estatus')->first();
    }

    public function planMantenimiento()
    {
        return $this->belongsTo(PlanMantenimiento::class, 'id_plan', 'id_plan');
    }
    public function fotos()
{
    // Asegúrate de que tu modelo OrdenFoto esté importado
    return $this->hasMany(OrdenFoto::class, 'orden_id'); 
}   

    // // En el modelo App\Models\Orden
    // public function solicitudesSuministros()
    // {
    //     return $this->hasMany(SolicitudMaterial::class, 'orden_trabajo_id');
    // }
    // En el modelo App\Models\SolicitudMaterial
    // public function ordenCompra()
    // {
    //     return $this->belongsTo(OrdenCompra::class, 'orden_compra_id'); // Puede ser null
    // }
}