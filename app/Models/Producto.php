<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'litros_tm',
        'stock',
        'stock_minimo',
        'categoria_iso',
        'densidad',
        'viscosidad',
        'azufre',
        'ctz',
        'proveedor',
        'aplicacion'
    ];

    public $timestamps = true;

    public function litrosPorTm($value)
    {
        if ($this->litros_tm && $this->litros_tm > 0) {
            return $value * $this->litros_tm;
        }
        return $value;
    }

    public function tmPorLitros($value)
    {
        if ($this->litros_tm && $this->litros_tm > 0) {
            return $value / $this->litros_tm;
        }
        return $value;
    }

    public function getPrecioPorLitroAttribute()
    {
        if ($this->litros_tm && $this->litros_tm > 0) {
            return $this->precio / $this->litros_tm;
        }
        return $this->precio;
    }   

}
