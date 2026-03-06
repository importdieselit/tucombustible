<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChoferCliente extends Model
{
    protected $table = 'choferes_clientes';
    protected $fillable = ['cliente_id', 'nombre_completo', 'cedula', 'activo'];

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}