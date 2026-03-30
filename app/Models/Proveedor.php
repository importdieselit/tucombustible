<?php

namespace App\Models; // O App\Models

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Tiene created_at y updated_at

    protected $fillable = [
        'nombre',
        'rif',
        'telefono',
        'email',
        'direccion',
        'id_tipo_proveedor',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id_tipo_proveedor' => 'integer',
    ];

    public function tipoProveedor()
    {
        return $this->belongsTo(TipoProveedor::class, 'id_tipo_proveedor');
    }
    
}