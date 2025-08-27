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

    protected $casts = [
        'codigo' => 'varchar:50',
        'cateforia' => 'varchar:50',
        'costo_mo' => 'decimal:2',
    ];

    // Relaciones

}