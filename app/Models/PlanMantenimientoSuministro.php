<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class PlanMantenimientoSuministro extends Model
{
    protected $table = 'plan_mantenimiento_suministro';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // La tabla no tiene created_at ni updated_at

    protected $fillable = [
        'id_plan',
        'id_servicio',
        'id_inventario',
        'cantidad',
        'id_usuario',
    ];

    protected $casts = [
        'id_plan' => 'integer',
        'id_servicio' => 'integer',
        'id_inventario' => 'integer',
        'cantidad' => 'integer',
        'id_usuario' => 'integer',
    ];

    // Relaciones
    public function planMantenimiento()
    {
        return $this->belongsTo(PlanMantenimiento::class, 'id_plan', 'id_plan');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}