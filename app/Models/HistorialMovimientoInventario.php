<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialMovimientoInventario extends Model
{
    use HasFactory;
    protected $table = 'historial_movimientos_inventario';
    protected $fillable = [
        'inventario_id',
        'ubicacion_id',
        'usuario_id',
        'tipo_movimiento', // ENTRADA, SALIDA, TRASLADO
        'tipo_operacion', // DESPACHO, DEVOLUCION, AJUSTE, TRASLADO
        'documento_referencia', // Número de orden, guía de remisión, etc.
        'cantidad_previa',
        'cantidad_movilizada',
        'cantidad_final',
        'observacion'
    ];
    protected $hasTimestamps = true;

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }



}
