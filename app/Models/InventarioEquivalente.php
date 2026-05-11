<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioEquivalente extends Model
{
    protected $table = 'inventario_equivalentes';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $fillable = ['id_inventario', 'id_equivalente', 'id_usuario'];

    public function itemPrincipal() {
        return $this->belongsTo(Inventario::class, 'id_inventario');
    }

    public function itemEquivalente() {
        return $this->belongsTo(Inventario::class, 'id_equivalente');
    }
}