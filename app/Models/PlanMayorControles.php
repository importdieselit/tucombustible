<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMayorControles extends Model
{
    use HasFactory;
    protected $table = 'plan_mayor_controles';
    protected $primaryKey = 'id';
    protected $fillable = [
        'vehiculo_id',
        'mantenimiento_item_id'
    ];

    public function mantenimientoItem()
    {
        return $this->belongsTo(MantenimientoItem::class, 'mantenimiento_item_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
