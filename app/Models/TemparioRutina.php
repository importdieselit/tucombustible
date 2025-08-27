<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemparioRutina extends Model
{
    protected $table = 'tempario_rutinas';
    protected $primaryKey = 'id_tempario_rutina';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'id_tempario_servicio',
        'id_plan',
        'ord'
    ];

    protected $casts = [
        'id_tempario_servicio' => 'integer',
        'id_plan' => 'integer',
        'ord' => 'integer'
    ];

    // Relaciones
    public function servicio()
    {
        return $this->belongsTo(TemparioServicio::class, 'id_tempario_servicio', 'id_tempario_servicio');
    }
    public function plan()
    {
        return $this->belongsTo(PlanMantenimiento::class, 'id_plan', 'id');
    }
}
