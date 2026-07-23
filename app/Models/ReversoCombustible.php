<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReversoCombustible extends Model
{
    use HasFactory;

    protected $table = 'reversos_combustible';

    protected $fillable = [
        'sede_id',
        'cliente_id',
        'tipo_combustible_id',
        'cantidad_litros',
        'motivo_reverso',
        'user_id',
    ];

    public function sede()
    {
        return $this->belongsTo(Sedes::class, 'sede_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tipoCombustible()
    {
        return $this->belongsTo(TipoCombustible::class, 'tipo_combustible_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}