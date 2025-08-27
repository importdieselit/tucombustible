<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class PlanMantenimiento extends Model
{
    protected $table = 'plan_mantenimiento';
    protected $primaryKey = 'id_plan';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_usuario',
        'nombre_plan',
        'descripcion',
        'kilometraje_programado',
        'fecha_programada',
        'estatus',
        'ultima_actualizacion_km',
        'ultima_actualizacion_fecha',
        'created_by',
        'updated_by',
        'id_tipo_vehiculo',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'kilometraje_programado' => 'integer',
        'fecha_programada' => 'date',
        'estatus' => 'integer',
        'ultima_actualizacion_km' => 'integer',
        'ultima_actualizacion_fecha' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'id_tipo_vehiculo' => 'integer',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // public function mantenimiento()
    // {
    //     return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    // }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id');
    }

    public function tipoVehiculo()
    {
        return $this->belongsTo(TipoVehiculo::class, 'id_tipo_vehiculo', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function suministros()
    {
        return $this->hasMany(PlanMantenimientoSuministro::class, 'id_plan', 'id_plan');
    }
}