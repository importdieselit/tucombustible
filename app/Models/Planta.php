<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TabuladorViatico;
use App\Models\Proveedor;

class Planta extends Model
{
    use HasFactory;

    protected $table = 'plantas';
    protected $fillable = ['nombre', 'direccion', 'telefono', 'alias', 'proveedor', 'id_tabulador_viatico'];
    

    public function tabuladorViatico()
    {
        return $this->belongsTo(TabuladorViatico::class, 'id_tabulador_viatico', 'id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor', 'id');
    }

}
