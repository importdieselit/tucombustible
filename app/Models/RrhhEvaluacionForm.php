<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RrhhEvaluacionForm extends Model
{
    protected $table = 'rrhh_evaluaciones_forms';

    protected $fillable = [
        'nombre',
        'cargo_id',
        'google_form_url',
        'activo'
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }
}
