<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCupo extends Model
{
    use HasFactory;

    protected $table = 'cliente_cupos';

    protected $fillable = [
        'cliente_id',
        'tipo_combustible_id',
        'litros_solicitados',
        'litros_aprobados',
    ];

    // -------------------------------------------------------
    // RELACIONES
    // -------------------------------------------------------

    /**
     * El cupo pertenece a un Cliente (Padre o Sucursal).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * El cupo está asociado a un tipo de combustible específico.
     */
    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }
}