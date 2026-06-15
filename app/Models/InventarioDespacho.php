<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioDespacho extends Model
{
    use HasFactory;
    protected $table = 'inventario_despachos';
    protected $fillable = [
        'id_orden',
        'inventario_id',
        'ubicacion_origen_id',
        'cantidad_solicitada',
        'cantidad_despachada',
        'observacion',
        'estatus',
        'usuario_solicita_id',
        'usuario_despacha_id',
        'fecha_despacho'
    ];
    protected $hasTimestamps = true;
    public function ordenTrabajo()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
    public function ubicacionOrigen()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }
    public function usuarioSolicita()
    {
        return $this->belongsTo(User::class, 'usuario_solicita_id');
    }
    public function usuarioDespacha()
    {
        return $this->belongsTo(User::class, 'usuario_despacha_id');
    }

}
