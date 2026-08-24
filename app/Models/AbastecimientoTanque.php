<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbastecimientoTanque extends Model
{
    protected $table = 'abastecimientos_tanques';

    protected $casts = [
    'fecha_hora' => 'datetime',
];

    protected $fillable = [
        'id_sede',
        'id_vehiculo',
        'id_deposito',
        'id_tipo_combustible',
        'id_usuario',
        'id_precarga_origen',
        'id_compra_combustible',
        'cantidad_litros',
        'fecha_hora',
        'observaciones',
    ];

    public function sede() { return $this->belongsTo(Sedes::class, 'id_sede'); }
    public function vehiculo() { return $this->belongsTo(Vehiculo::class, 'id_vehiculo'); }
    public function deposito() { return $this->belongsTo(Deposito::class, 'id_deposito'); }
    public function tipoCombustible() { return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible'); }
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario'); }
    public function precargaOrigen() { return $this->belongsTo(VehiculoPrecargado::class, 'id_precarga_origen'); }
    public function compraCombustible() { return $this->belongsTo(CompraCombustible::class, 'id_compra_combustible'); }
}