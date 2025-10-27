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
        'titulo',
        'descripcion',
        'kilometraje',
        'tiempo',
        'short',
        'rango_min_t',
        'rango_max_t',
        'rango_min_k',
        'rango_max_k',
    ];

    protected $casts = [
        'kilometraje' => 'integer',
        'tiempo' => 'integer',
        'rango_min_t' => 'integer',
        'rango_max_t' => 'integer',
        'rango_min_k' => 'integer',
        'rango_max_k' => 'integer'
    ];

    
}