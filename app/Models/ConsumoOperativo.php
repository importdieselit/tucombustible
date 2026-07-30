<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumoOperativo extends Model
{
    use HasFactory;

    protected $table = 'consumos_operativos';

    protected $fillable = [
        'sede_id',
        'vehiculo_id',
        'equipo_maquinaria',
        'deposito_id',
        'tipo_combustible_id',
        'cantidad_litros',
        'user_id',
        'observaciones',
    ];

    public function sede()
    {
        return $this->belongsTo(Sedes::class, 'sede_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function deposito()
    {
        return $this->belongsTo(Deposito::class, 'deposito_id');
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