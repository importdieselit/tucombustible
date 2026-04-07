<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoFalla extends Model
{
    use HasFactory;
    protected $table = 'tipo_fallas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'tipo_falla',
        'id_tipo_orden',
    ];

    public function tipoOrden()
    {
        return $this->belongsTo(TipoOrden::class, 'id_tipo_orden');
    }

}
