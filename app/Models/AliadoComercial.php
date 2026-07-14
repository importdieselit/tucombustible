<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AliadoComercial extends Model {
    protected $table = 'aliados_comerciales';
    protected $fillable = ['nombre_empresa', 'rif', 'activo'];
}