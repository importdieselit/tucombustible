<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class TemparioCategoria extends Model
{
    protected $table = 'tempario_categorias';
    protected $primaryKey = 'id_tempario_categoria';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'codigo',
        'categoria',
        'costo_mo'
    ];


    function servicios()
    {
        return $this->hasMany(TemparioServicio::class, 'id_tempario_categoria', 'id_tempario_categoria');
    }   
    function trabajos()
    {
        return $this->hasMany(Trabajos::class, 'id_categoria', 'id_tempario_categoria');
    }

    // Relaciones

}