<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabajoExterno extends Model
{
    use HasFactory;
    protected $table = 'trabajos_externos';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    public $softDeletes = true;
    public $fillable = ['descripcion', 'fecha', 'costo', 'id_usuario', 'id_proveedor', 'id_orden'];
    public $casts = [];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id', 'id_usuario');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class,'id_proveedor', 'id');
    }

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'id','id_orden' );
    }

}
