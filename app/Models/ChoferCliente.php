<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChoferCliente extends Model
{
    protected $table = 'choferes_clientes';

    protected $fillable = [
        'cliente_id',
        'nombre_completo',
        'cedula',
        'activo',
    ];

    // -------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------

    /**
     * El chofer pertenece a un Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // -------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------

    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
}